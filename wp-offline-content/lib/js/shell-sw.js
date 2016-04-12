(function(self, localforage){

  // Represents the name of both storage and serviceworker
  // This shouldn't change because the SW code below is smart enough to
  // update what's needed and delete what isn't
  var storageKey = '$name';

  var wpOfflineShell = self.wpOfflineShell = {
    // A { url: hash } object which will be used to populate cache
    // A changed url object will ensure the SW is downloaded when URLs change
    urls: $urls,
    // A list of enqueued URLs which should be cached, either in the background or with asset URLs
    enqueues: $enqueues,
    // Setting for when to load enqueued URLs - 1 is background, 0 is with other URLs
    enqueuesBackground: $enqueuesBackground,
    // Allowed to use console functions?
    debug: $debug,
    // Race cache-network or only cache?
    raceEnabled: $raceEnabled,
    // Instance of localForage to save urls and hashes to see if anything has changed
    storage: localforage.createInstance({ name: storageKey }),
    // Name of the cache the plugin will use
    cacheName: storageKey,
    // Method to cleanse a URL before comparing and caching
    ignoreSearch: function(request) {
      var url = new URL(request.url);
      if (url.origin !== location.origin) {
        return request.url;
      }

      url.search = '';
      url.fragment = '';

      // Cast to string so this function always returns a string
      return url + '';
    },
    // Detect if a URL should be cacheable and in the desired URL list
    shouldBeHandled: function(method, url) {
      return method === 'GET' && ((url in this.urls) || (this.enqueues.indexOf(url) !== -1));
    },
    // Adds URLs to cache and localForage if the file has changed or needs to be added
    update: function() {
      var urls = Object.keys(this.urls);

      // Handle enqueue files first
      if(this.enqueues.length) {
        if(this.enqueuesBackground) {
          // Will have no affect on service worker installation
          self.caches.open(this.cacheName).then(cache => {
            this.enqueues.forEach(url => {
              cache.add(url).then(() => {
                this.log('[update] Caching enqueued URL in the background: ', url);
              });
            });
          });
        }
        else {
          // Add to the rest of URLs to be cached
          // Will affect install success or failure
          urls.concat(this.enqueues);
          this.log('[update] Adding enqueued URLs to the main cache list.');
        }
      }

      // For every URL (file) the user wants cached...
      return Promise.all(urls.map(url => {
        var hash = this.urls[url];

        // ... get its hash from storage ...
        return this.storage.getItem(url).then(value => {

          console.log('value is: ', value, '; hash is: ', hash);

          // ... and if nothing has changed, just move on to the next URL
          if(hash !== undefined && value === hash) {
            this.log('[update] Hash unchanged, doing nothing: ', url);
            return Promise.resolve();
          }

          this.log('[update] Hash changed or new URL, adding to cache "' + this.cacheName +'" : ' , url);
          // ... Add the new/updated URL and its response to cache ...
          return self.caches.open(this.cacheName).then(cache => {
            return cache.add(url).then(() => {
              // ... and once it's successful add its hash to storage
              if(hash !== undefined) {
                return this.storage.setItem(url, hash);
              }
            });
          });

        })
        .catch(e => {
          this.warn('[update] error: ', e);
        });
      }));
    },
    // Check each URL in cache and delete anything that shouldn't be there anymore
    // i.e. the user unchecked a file's box in admin
    removeOldUrls: function() {
      return caches.open(this.cacheName).then(cache =>  {
        return cache.keys().then(keys =>  {
          return Promise.all(keys.map(key => {
            if(!(key.url in this.urls) && (this.enqueues.indexOf(key.url) === -1)) {
              return this.removeOldUrl(cache, key);
            }
            return Promise.resolve();
          }));
        })
      })
      .catch(e => {
        this.warn('[removeOldUrls] error: ', e);
      });
    },
    // Removes one individual URL from cache and storage
    removeOldUrl: function(cache, request) {
      this.log('[removeOldUrl] Removing URL no longer desired: ', request.url);
      return cache.delete(request).then(() => {
        return this.storage.removeItem(request.url);
      });
    },
    // Install step that kicks off adding/updating URLs in cache and storage
    onInstall: function(event) {
      this.log('[install] Event triggered');
      this.log('[install] Initial theme cache list is: ', Object.keys(this.urls));
      this.log('[install] Initial enqueue cache list is: ', this.enqueues);

      event.waitUntil(Promise.all([self.skipWaiting(), this.update()]));
    },
    // Does cleanup after everything went well
    onActivate: function(event) {
      this.log('[activate] Event triggered');
      event.waitUntil(Promise.all([self.clients.claim(), this.removeOldUrls()]));
    },
    // Manages returning responses from cache or the server
    onFetch: function(event) {
      var request = event.request;

      var url = this.ignoreSearch(request);
      if (!this.shouldBeHandled(request.method, url)) {
        return;
      }

      var gotFromCache = false;
      var gotFromNetwork = false;

      var fromCache = caches.match(url)
      .then(response => {
        gotFromCache = true;
        return response;
      })
      .catch(e => {
        this.warn('[fetch] error: ', e);
      });

      var promise;
      if (this.raceEnabled) {
        var fromNetwork = fetch(request)
        .then(response => {
          gotFromNetwork = true;
          return response;
        });

        promise = Promise.race([ fromCache, fromNetwork ])
        .then(response => {
          if (gotFromCache) {
            if (response) {
              this.log('[fetch] Cache hit, returning from ServiceWorker cache: ', event.request.url);
            } else {
              this.log('[fetch] Cache miss, retrieving from server: ', event.request.url);
            }
          } else {
            this.log('[fetch] Retrieved from server: ', event.request.url);
          }

          // If we couldn't find the resource in the cache, we have to wait for the
          // network request to finish.
          return response || fromNetwork;
        });
      } else {
        promise = fromCache
        .then(response => {
          if (response) {
            this.log('[fetch] Cache hit, returning from ServiceWorker cache: ', event.request.url);
            return response;
          }

          // If we couldn't find the resource in the cache, we have to perform a
          // network request.
          this.log('[fetch] Cache miss, retrieving from server: ', event.request.url);
          return fetch(request);
        });
      }

      event.respondWith(promise);
    }
  };

  // Add debugging functions
  ['log', 'warn'].forEach(function(level) {
    wpOfflineShell[level] = function() {
      if(this.debug) {
        console[level].apply(console, arguments);
      }
    };
  });

  // Kick off the event listeners
  self.addEventListener('install', wpOfflineShell.onInstall.bind(wpOfflineShell));
  self.addEventListener('activate', wpOfflineShell.onActivate.bind(wpOfflineShell));
  self.addEventListener('fetch', wpOfflineShell.onFetch.bind(wpOfflineShell));

})(self, localforage);
