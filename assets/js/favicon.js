/*
 * | Index | Approaches            | Websites           |
 * |-------+-----------------------+--------------------|
 * |     1 | favicon.ico           | https://github.com |
 * |     2 | <link rel*="icon">    | https://github.com |
 * |     3 | manifest.json         | https://github.com |
 * |     4 | browserconfig.xml     |                    |
 */

// 1. Get favicon from favicon.ico
// e.g. https://github.com/favicon.ico
async function getFaviconIcoUrl(url) {
    const faviconIcoUrl = new URL(url).origin + '/favicon.ico'
    const response = await fetch(faviconIcoUrl)
    return response.status === 200 ? faviconIcoUrl : ''
}

// 2.  Get favicons in <link>
/*
 * | Websites             | Favicon href values                                            |
 * |----------------------+----------------------------------------------------------------|
 * | https://github.com   | https://github.githubassets.com/favicons/favicon.png           |
 * | https://jquery.com   | //jquery.com/jquery-wp-content/themes/jquery.com/i/favicon.ico |
 * | https://pixabay.com  | /favicon-32x32.png                                             |
 * | https://willbc.cn    | assets/images/favicon.png                                      |
 */

// Note: A simple way to work in the browser console.
// function getFaviconURLs() {
//     const links = document.querySelectorAll('link[rel*=icon]')
//     return [...links].map(link => link.href)
// }

// 2.1 Get HTML from url
// Alternative, only if you want to use it in browser extension or server.
// It may not work in the browser console.
async function getHTML(url) {
    const response = await fetch(url)
    const text = await response.text()
    const parser = new DOMParser()
    const html = parser.parseFromString(text, 'text/html')

    return html
}

// 2.2 Get favicon urls from HTML
function getFaviconURLs(html, url) {
    const links = html.querySelectorAll('link[rel*=icon]')
    const faviconURLs = [...links].map(getFaviconURL)

    return faviconURLs

    function getFaviconURL(link) {
        // link.href will prefix extension url for browser extensions
        const href = link.getAttribute('href')
        const origin = new URL(url).origin

        if (isURL(href)) return href
        if (href.startsWith('//')) return 'https:' + href
        if (href.startsWith('/')) return origin + href

        return origin + '/' + href
    }

    function isURL(url) {
        return /^https?:\/\//.test(url)
    }
}

// 2.3 Sort Favicons by name and quality.
function sortByPriority(favicons) {
    return favicons
        .map(assignPriority)
        .sort(byPriority)
        .map(removePriority)

    function assignPriority(favicon) {
        let priority = 0
        if (favicon.includes('favicon')) priority += 1
        if (favicon.includes('32')) priority += 1

        return {url: favicon, priority}
    }

    function byPriority(a, b) {
        return b.priority - a.priority
    }

    function removePriority({url}) {
        return url
    }
}

// Alternative: Convert favicon from url to Base64 data url
async function getDataURL(faviconURL) {
    const response = await fetch(faviconURL)
    const blob = await response.blob()
    const dataURL = await toDataURL(blob)

    return dataURL

    async function toDataURL(blob) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader()
            reader.onloadend = () => resolve(reader.result)
            reader.onerror = reject
            reader.readAsDataURL(blob)
        })
    }
}

const fallbackSVG = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="#4a5568"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`

// Example
async function getFaviconURL(url) {
    try {
        let faviconURL = await getFaviconIcoUrl(url)
        if (faviconURL !== '') return faviconURL

        const html = await getHTML(url)
        let faviconURLs = getFaviconURLs(html, url)
        faviconURLs = sortByPriority(faviconURLs)
        faviconURL = faviconURLs[0]
        // You can return the DataURL if you need.
        // const faviconDataURL = await getDataURL(faviconURL)

        return faviconURL
    } catch (_) {
        return fallbackSVG
    }
}

// Test
function test(urls) {
    urls.map(async url => {
        const favicon = await getFaviconURL(url)
        console.log(url, favicon)
        // window.open(favicon)
    })
}

const urls = [
    "https://github.com",
    "https://jquery.com",
    "https://pixabay.com",
    "https://willbc.cn",
    "not a url",
    "https://unavaliable.com", // This URL takes time to get result.
    "https://stackoverflow.com/questions/61212",
    "https://www.google.com/search?q=safari+read+later",
    "https://www.hanost.com",
]

// Uncomment the code below to test
getHTML("https://github.com")
