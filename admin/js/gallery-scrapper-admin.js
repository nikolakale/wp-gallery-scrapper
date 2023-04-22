const emptySettingsObj = {
    path: "",
    source: "",
    attribute: "",
    scrape: "",
    alt: ""
}
function removeLoader() {
    const container = document.querySelector('.gallery-scrapper-loading');
    if (container) {
        container.remove();
    }
}

function createLoader() {
    const containerExists = document.querySelector('.gallery-scrapper-loading');
    if (!containerExists) {
        const container = document.createElement("div");
        container.className = 'gallery-scrapper-loading';
        const spinner = document.createElement("div");
        spinner.className = 'lds-roller';
        spinner.appendChild(document.createElement("div"));
        spinner.appendChild(document.createElement("div"));
        spinner.appendChild(document.createElement("div"));
        spinner.appendChild(document.createElement("div"));
        spinner.appendChild(document.createElement("div"));
        spinner.appendChild(document.createElement("div"));
        spinner.appendChild(document.createElement("div"));
        spinner.appendChild(document.createElement("div"));
        container.appendChild(spinner);
        document.body.appendChild(container);
    }
}

function syncGalleries(postId = null, scrapeId = null, startCallback = null, endCallback = null) {
    const data = new FormData();

    data.append('action', 'sync_now');
    data.append('nonce', wp_sync_now.nonce);
    data.append('post_id', postId);
    data.append('scrape_id', scrapeId)
    
    createLoader();
    
    fetch(wp_sync_now.ajax_url, {
        method: "POST",
        credentials: 'same-origin',
        body: data
    })
        .then((response) => response.json())
        .then((data) => {
            removeLoader();
            window.location.reload();
            console.log(data);
            if (data) {
                alert(data.message);
            }
        })
        .catch((error) => {
            removeLoader();
            
            console.log('[WP Sync now failed]');
            console.error(error);
            alert('WP Sync now failed');

        });
};

const mountEl = document.querySelector("#app");
console.log('mountEl.dataset', mountEl.dataset);
const {createApp} = Vue;

createApp({
    props: ["sourcesdata", "scrapesdata"],
    mounted() {
        this.sources = JSON.parse(this.sourcesdata);
        this.scrapes = JSON.parse(this.scrapesdata);
    },
    methods: {
        add() {
            this.sources.push({...emptySettingsObj});
        },
        remove(index) {
            this.sources.splice(index, 1);
        },
        syncNow() {
            syncGalleries();
        }
    },
    computed: {
        sourcesJson() {
            return JSON.stringify(this.sources)
        }
    },
    data() {
        return {
            scrapes: [],
            sources: []
        }
    }
}, {...mountEl.dataset}).mount('#app')

