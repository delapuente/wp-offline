(function (self, localforage) {
  var PRIVATE_NAME = '__wp-offline-content';

  var CACHE_PREFIX = PRIVATE_NAME + '::';

  var wpOfflineContent = self.wpOfflineContent = {

    version: $version,

    storage: localforage.createInstance({ name: PRIVATE_NAME }),

    resources: $resources,

    excludedPaths: $excludedPaths,

    debug: $debug,

    cacheName: CACHE_PREFIX + 'v1',

    networkTimeout: $networkTimeout,

    log: function () {
      if (this.debug) {
        console.log.apply(console, arguments);
      }
    },

    origin: self.location.origin,

    onInstall: function (event) {
      event.waitUntil(Promise.all([
        self.skipWaiting(),
        wpOfflineContent.update()
      ]));
    },

    onActivate: function (event) {
      event.waitUntil(Promise.all([self.clients.claim(), this.deleteOutdatedCaches(CACHE_PREFIX)]));
    },

    onFetch: function (event) {
      var request = event.request;
      if (this.shouldBeHandled(request)) {
        event.respondWith(wpOfflineContent.get(request));
      }
    },

    shouldBeHandled: function (request) {
      return request.method === 'GET' && !this.isExcluded(request.url);
    },

    update: function () {
      return this.needsUpdate().then(updateIsNeeded => {
        if (updateIsNeeded) {
          return this.storage.getItem('resources')
          .then(currents => this.computeUpdateOrder(currents || {}, this.resources))
          .then(order => this.doOrder(order))
          .then(() => this.storage.setItem('resources', this.resources))
          .then(() => this.storage.setItem('version', this.version));
        }
        return Promise.resolve();
      });
    },

    needsUpdate: function () {
      return this.storage.getItem('version')
      .then(lastVersion => Promise.resolve(lastVersion !== this.version));
    },

    computeUpdateOrder: function (currentContent, newContent) {
      var order = {
        remove: [],
        update: [],
        addnew: []
      };
      var currentUrls = Object.keys(currentContent);
      currentUrls.forEach(url => {
        if (!(url in newContent)) {
          order.remove.push([url, currentContent[url]]);
        }
        else if (currentContent[url] !== newContent[url]) {
          order.update.push([url, newContent[url]]);
        }
      });
      var newUrls = Object.keys(newContent);
      newUrls.forEach(newUrl => {
        if (!(newUrl in currentContent)) {
          order.addnew.push([newUrl, newContent[newUrl]]);
        }
      });
      return order;
    },

    doOrder: function(order) {
      return Promise.all([
        this._deleteFromCache(order.remove),
        this._deleteFromCache(order.update).then(() => this._cacheFromNetwork(order.update)),
        this._cacheFromNetwork(order.addnew)
      ]);
    },

    _deleteFromCache: function(deletions) {
      return this.openCache()
      .then(cache => Promise.all(deletions.map(deletion => {
        var url = deletion[0];
        return cache.delete(url);
      })));
    },

    _cacheFromNetwork: function(resources) {
      return this.openCache()
      .then(cache => Promise.all(resources.map(resource => {
        var url = resource[0];
        return self.fetch(url)
          .then(response => {
            if (response.ok) {
              return cache.put(url, response);
            }
            this.log('Error fetching', url);
            return Promise.resolve();
          });
      })));
    },

    deleteOutdatedCaches: function (prefix) {
      return self.caches.keys().then(names => {
        return Promise.all(names.map(cacheName => {
          if (cacheName.startsWith(prefix) && cacheName !== this.cacheName) {
            return self.caches.delete(cacheName);
          }
          return Promise.resolve();
        }));
      });
    },

    get: function (request) {
      var url = request.url;
      this.log('Fetching', url);

      var fetchFromNetwork = fetch(request.clone())
      .catch(error => {
        this.log('Failed to fetch', url);
        throw error;
      });

      var fetchAndCache = fetchFromNetwork.then(responseFromNetwork => {
        if (responseFromNetwork && responseFromNetwork.ok) {
          this.log('Caching', responseFromNetwork.url);
          this.openCache()
          .then(cache => cache.put(request.clone(), responseFromNetwork.clone()));
        }
      });

      var waitForNetwork = new Promise((fulfill, reject) => {
        var expired = false;

        var timeout = setTimeout(() => {
          this.log('Timeout for', url);
          expired = true;
          reject();
        }, this.networkTimeout);

        fetchFromNetwork
        .then(
          responseFromNetwork => {
            if (!expired) {
              clearTimeout(timeout);
              if (!responseFromNetwork) {
                this.log('Undefined response for', url);
                reject('network-error');
              } else {
                this.log('Success from network for', url);
                fulfill(responseFromNetwork.clone());
              }
            }
          },
          error => {
            if (!expired) {
              this.log('Network error for', url);
              clearTimeout(timeout);
              reject(error);
            }
          }
        );
      });

      var fetchFromCache = self.caches.match(request.clone()).catch(error => console.error(error));

      return waitForNetwork
        .catch(() => fetchFromCache.then(responseFromCache => {
          if (!responseFromCache) {
            this.log('Cache miss for', url);
            return fetchFromNetwork;
          }
          this.log('Cache hit for', url);
          return responseFromCache;
        }));
    },

    isExcluded: function (url) {
      return this.isAnotherOrigin(url) ||
             this.excludedPaths.some(path => url.startsWith(path));
    },

    openCache: function () {
      if (!this._openCache) {
        this._openCache = self.caches.open(this.cacheName);
      }
      return this._openCache;
    },

    isAnotherOrigin: function (url) {
      return !url.startsWith(this.origin);
    }
  };

  self.addEventListener('install', wpOfflineContent.onInstall.bind(wpOfflineContent));
  self.addEventListener('activate', wpOfflineContent.onActivate.bind(wpOfflineContent));
  self.addEventListener('fetch', wpOfflineContent.onFetch.bind(wpOfflineContent));

})(self, localforage);
