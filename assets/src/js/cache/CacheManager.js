// cache/CacheManager.js
import CacheDB from './CacheDB.js';

export default class CacheManager {
    constructor(options = {}) {
        const {
            cacheDB = null,
            storage = 'session',
            defaultExpiry = 60 * 1000,
            namespace = 'appcache',
            onError = (e) => console.warn('CacheManager error:', e),
        } = options;

        this.cacheDB = cacheDB instanceof CacheDB ? cacheDB : null;
        this.storage = storage === 'local' ? 'local' : 'session';
        this.defaultExpiry = defaultExpiry;
        this.namespace = String(namespace || 'appcache');
        this.onError = onError;
    }

    _namespaced(key) {
        return `${this.namespace}::${key}`;
    }

    _storage() {
        return this.storage === 'local' ? localStorage : sessionStorage;
    }

    async set(key, data, expiry = this.defaultExpiry) {
        const ns = this._namespaced(key);
        if (this.cacheDB) {
            try {
                await this.cacheDB.set(ns, data, expiry);
                return true;
            } catch (e) {
                this.onError(e);
                // fall through to fallback
            }
        }

        try {
            const store = this._storage();
            const entry = { data, timestamp: Date.now(), expiry };
            store.setItem(ns, JSON.stringify(entry));
            return true;
        } catch (e) {
            this.onError(e);
            return false;
        }
    }

    async get(key) {
        const entry = await this.getEntry(key);
        return entry ? entry.data : null;
    }

    async getEntry(key) {
        const ns = this._namespaced(key);

        if (this.cacheDB) {
            try {
                const entry = await this.cacheDB.getEntry(ns);
                if (entry) {
                    return { data: entry.data, timestamp: entry.timestamp, expiry: entry.expiry };
                }
                return null;
            } catch (e) {
                this.onError(e);
                // fall through
            }
        }

        try {
            const store = this._storage();
            const raw = store.getItem(ns);
            if (!raw) return null;
            const parsed = JSON.parse(raw);
            if (!parsed || typeof parsed.timestamp !== 'number' || typeof parsed.expiry !== 'number') {
                try { store.removeItem(ns); } catch (_) { }
                return null;
            }
            if (Date.now() - parsed.timestamp > parsed.expiry) {
                try { store.removeItem(ns); } catch (_) { }
                return null;
            }
            return { data: parsed.data, timestamp: parsed.timestamp, expiry: parsed.expiry };
        } catch (e) {
            this.onError(e);
            return null;
        }
    }

    async isValid(key) {
        try {
            const entry = await this.getEntry(key);
            return Boolean(entry);
        } catch (e) {
            this.onError(e);
            return false;
        }
    }

    async delete(key) {
        const ns = this._namespaced(key);
        if (this.cacheDB) {
            try {
                await this.cacheDB.delete(ns);
                return true;
            } catch (e) {
                this.onError(e);
            }
        }
        try {
            const store = this._storage();
            store.removeItem(ns);
            return true;
        } catch (e) {
            this.onError(e);
            return false;
        }
    }

    async clear() {
        if (this.cacheDB) {
            try {
                await this.cacheDB.clear();
                return true;
            } catch (e) {
                this.onError(e);
                // fall through to namespaced cleanup
            }
        }

        try {
            const store = this._storage();
            const toRemove = [];
            for (let i = 0; i < store.length; i++) {
                const k = store.key(i);
                if (k && k.startsWith(`${this.namespace}::`)) toRemove.push(k);
            }
            toRemove.forEach((k) => store.removeItem(k));
            return true;
        } catch (e) {
            this.onError(e);
            return false;
        }
    }

    /**
     * staleWhileRevalidate(key, fetcher, { expiry, background })
     * - fetcher: async function that returns fresh data
     * - if cached: return cached immediately and optionally refresh in background
     * - if not cached: await fetcher(), cache and return
     */
    async staleWhileRevalidate(key, fetcher, options = {}) {
        const { expiry = this.defaultExpiry, background = true } = options;

        try {
            const entry = await this.getEntry(key);
            if (entry) {
                const cached = entry.data;
                if (background) {
                    (async () => {
                        try {
                            const fresh = await fetcher();
                            await this.set(key, fresh, expiry);
                        } catch (e) {
                            this.onError(e);
                        }
                    })();
                }
                return cached;
            }

            const fresh = await fetcher();
            await this.set(key, fresh, expiry);
            return fresh;
        } catch (e) {
            this.onError(e);
            // last resort: attempt direct fetch
            try {
                const fresh = await fetcher();
                try { await this.set(key, fresh, expiry); } catch (_) { }
                return fresh;
            } catch (err) {
                this.onError(err);
                return null;
            }
        }
    }
}
