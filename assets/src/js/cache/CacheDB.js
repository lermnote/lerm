export default class CacheDB {
    constructor(dbName = 'PageCacheDB', storeName = 'pages', version = 1) {
        this.dbName = dbName;
        this.storeName = storeName;
        this.version = version;
    }

    async openDB() {
        return new Promise((resolve, reject) => {
            try {
                const request = indexedDB.open(this.dbName, this.version);
                request.onupgradeneeded = (event) => {
                    const db = event.target.result;
                    if (!db.objectStoreNames.contains(this.storeName)) {
                        db.createObjectStore(this.storeName, { keyPath: 'key' });
                    }
                };
                request.onsuccess = (event) => resolve(event.target.result);
                request.onerror = (event) => reject(event.target.error);
            } catch (err) {
                reject(err);
            }
        });
    }

    async set(key, data, expiry = 5 * 60 * 1000) {
        const db = await this.openDB();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(this.storeName, 'readwrite');
            const store = tx.objectStore(this.storeName);
            const entry = { key, data, timestamp: Date.now(), expiry };
            const req = store.put(entry);
            req.onsuccess = () => resolve(true);
            req.onerror = (e) => reject(e.target?.error ?? e);
        });
    }

    // 返回完整条目或 null（并且自动删除过期项）
    async getEntry(key) {
        const db = await this.openDB();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(this.storeName, 'readonly');
            const store = tx.objectStore(this.storeName);
            const req = store.get(key);
            req.onsuccess = async (event) => {
                const result = event.target.result;
                if (!result) return resolve(null);
                if (Date.now() - result.timestamp > result.expiry) {
                    // expired -> delete then return null
                    try {
                        const txd = db.transaction(this.storeName, 'readwrite');
                        txd.objectStore(this.storeName).delete(key);
                    } catch (_) { }
                    return resolve(null);
                }
                resolve(result);
            };
            req.onerror = (e) => reject(e.target?.error ?? e);
        });
    }

    async get(key) {
        const entry = await this.getEntry(key);
        return entry ? entry.data : null;
    }

    async delete(key) {
        const db = await this.openDB();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(this.storeName, 'readwrite');
            const store = tx.objectStore(this.storeName);
            const req = store.delete(key);
            req.onsuccess = () => resolve(true);
            req.onerror = (e) => reject(e.target?.error ?? e);
        });
    }

    async clear() {
        const db = await this.openDB();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(this.storeName, 'readwrite');
            const store = tx.objectStore(this.storeName);
            const req = store.clear();
            req.onsuccess = () => resolve(true);
            req.onerror = (e) => reject(e.target?.error ?? e);
        });
    }
}
