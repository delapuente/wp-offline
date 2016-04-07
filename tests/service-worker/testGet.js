var NETWORK_TIMEOUT = 100;
var MORE_THAN_NETWORK_TIMEOUT = NETWORK_TIMEOUT + 10;
var LESS_THAN_NETWORK_TIMEOUT = NETWORK_TIMEOUT - 10;

var $version = '';
var $debug = false;
var $resources = [];
var $excludedPaths = [];
var $cacheName = 'testCache';
var $networkTimeout = NETWORK_TIMEOUT;

describe('get()', function() {
  'use strict';

  var clock;
  var fakeCache;

  function testWithTimeout(request) {
    return testAdvancingTime(request, MORE_THAN_NETWORK_TIMEOUT);
  }

  function testWithoutTimeout(request) {
    return testAdvancingTime(request, LESS_THAN_NETWORK_TIMEOUT);
  }

  function testAdvancingTime(request, time) {
    var result = wpOfflineContent.get(request);
    clock.tick(time);
    return result;
  }

  beforeEach(function() {
    fakeCache = {
      put: sinon.stub().returns(Promise.resolve())
    };
    clock = sinon.useFakeTimers();
    importScripts('/base/wp-offline-content/lib/js/content-sw.js');
    sinon.stub(wpOfflineContent, 'openCache').returns(Promise.resolve(fakeCache));
    sinon.stub(Response.prototype, 'clone').returnsThis();
  });

  afterEach(function() {
    clock.restore();
    Response.prototype.clone.restore();
  });

  // TODO: It remains to check clone(). This can bit us.
  describe('get() when network is available and it does not time out', function() {
    var networkResponse = new Response('success!');

    before(function() {
      sinon.stub(self, 'fetch').returns(Promise.resolve(networkResponse));
    });

    after(function() {
      self.fetch.restore();
    });

    it('fetches from network', function() {
      return testWithoutTimeout(new Request('test/url'))
        .then(response => {
          assert.strictEqual(response, networkResponse);
        });
    });

    it('stores a fresh copy in the cache', function() {
      var request = new Request('some/url');
      return testWithoutTimeout(request)
        .then(() => {
          assert.isOk(fakeCache.put.calledOnce);
          assert.isOk(fakeCache.put.calledWith(request, networkResponse));
        });
    });

  });

  describe('get() when network is available but times out', function() {
    var networkResponse = new Response('network success!');
    var cacheResponse = new Response('cache success!');

    before(function() {
      sinon.stub(self, 'fetch').returns(new Promise(fulfil => {
        setTimeout(() => fulfil(networkResponse), (NETWORK_TIMEOUT + MORE_THAN_NETWORK_TIMEOUT)/2);
      }));
    });

    after(function() {
      self.fetch.restore();
    });

    afterEach(function() {
      if (self.caches.match.restore) { self.caches.match.restore(); }
    });

    it('fetches from cache if there is a match', function() {
      sinon.stub(self.caches, 'match').returns(Promise.resolve(cacheResponse));
      return testWithTimeout(new Request('test/url'))
      .then(response => {
        assert.strictEqual(response, cacheResponse);
      });
    });

    it('stores a fresh copy in the cache', function() {
      var request = new Request('some/url');
      return testWithTimeout(request)
        .then(() => {
          assert.isOk(fakeCache.put.calledOnce);
          assert.isOk(fakeCache.put.calledWith(request, networkResponse));
        });
    });
  });

  describe('get() when network is not available', function() {
    var networkError = {};
    var cacheResponse = new Response('cache success!');

    before(function() {
      sinon.stub(self, 'fetch').returns(Promise.reject(networkError));
    });

    after(function() {
      self.fetch.restore();
    });

    afterEach(function() {
      if (self.caches.match.restore) { self.caches.match.restore(); }
    });

    it('fetches from cache if there is a match', function() {
      sinon.stub(self.caches, 'match').returns(Promise.resolve(cacheResponse));
      return wpOfflineContent.get(new Request('/test/url'))
      .then(response => {
        assert.strictEqual(response, cacheResponse);
      });
    });

    it('error if there is no match', function() {
      sinon.stub(self.caches, 'match').returns(Promise.resolve(undefined));
      return wpOfflineContent.get(new Request('test/url'))
      .then(response => {
        assert.isOk(false);
      })
      .catch(error => {
        assert.strictEqual(error, networkError);
      });
    });

  });
});
