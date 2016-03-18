describe('update()', function() {
  'use strict';

  function update(item) {
    item[1] += 1;
    return item;
  }

  function toMap(list) {
    return list.reduce((obj, item) => {
      obj[item[0]] = item[1];
      return obj;
    }, {});
  }

  var items = {
    get a() { return ['/path/to/a', '1']; },
    get b() { return ['/path/to/b', '2']; },
    get c() { return ['/path/to/c', '3']; },
    get d() { return ['/path/to/d', '4']; }
  };

  beforeEach(function() {
    importScripts('/base/wp-offline-content/lib/js/content-sw.js');
  });

  afterEach(function() {
  });

  describe('needsUpdate()', function() {
    var swVersion = 'a-version';
    var sameVersion = swVersion;
    var otherVersion = 'other-version';

    it('resolves to true if version stored and in the sw do not match', function () {
      wpOfflineContent.version = swVersion;
      wpOfflineContent.storage.getItem =
        sinon.stub().withArgs('version').returns(Promise.resolve(otherVersion));

      return wpOfflineContent.needsUpdate()
      .then(function(isUpdateNeeded) {
        assert.isTrue(isUpdateNeeded);
      });
    });

    it('resolves to false if version stored and in the sw match', function () {
      wpOfflineContent.version = swVersion;
      wpOfflineContent.storage.getItem =
        sinon.stub().withArgs('version').returns(Promise.resolve(sameVersion));

      return wpOfflineContent.needsUpdate()
        .then(function(isUpdateNeeded) {
          assert.isFalse(isUpdateNeeded);
        });
    });
  });

  describe('computeUpdateOrder()', function () {

    it('computes the sets for the different actions: to remove, to update and add new',
       function () {
         var updatedC = update(items.c);

         var expected = {
           remove: [items.a],
           update: [updatedC],
           addnew: [items.d]
         };

         var oldSet = toMap([items.a, items.b, items.c]);
         var newSet = toMap([items.b, updatedC, items.d]);

         var result = wpOfflineContent.computeUpdateOrder(oldSet, newSet);
         assert.deepEqual(result.update, expected.update);
         assert.deepEqual(result, expected);
       });

    it('computes that all need to be added new if the old set is empty',
       function () {
         var expected = {
           remove: [],
           update: [],
           addnew: [items.a, items.b, items.c, items.d]
         };

         var oldSet = toMap([]);
         var newSet = toMap([items.a, items.b, items.c, items.d]);

         var result = wpOfflineContent.computeUpdateOrder(oldSet, newSet);
         assert.deepEqual(result, expected);
       });

  });

  describe('doOrder()', function () {

    var fakeCache;
    var networkResponse = { ok: true };

    beforeEach(function() {
      fakeCache = {
        put: sinon.stub().returns(Promise.resolve()),
        delete: sinon.stub().returns(Promise.resolve())
      };
      sinon.stub(self, 'fetch').returns(Promise.resolve(networkResponse));
      sinon.stub(wpOfflineContent, 'openCache').returns(Promise.resolve(fakeCache));
    });

    afterEach(function() {
      self.fetch.restore();
      wpOfflineContent.openCache.restore();
    });

    it('update the cache, fetching from the network when needed',
       function() {
         var a = items.a;
         var b = items.b;
         var c = items.c;

         var order = {
           remove: [a],
           update: [b],
           addnew: [c]
         };

         return wpOfflineContent.doOrder(order)
         .then(() => {
           var expectedDeletions = order.remove.concat(order.update);
           assert.equal(fakeCache.delete.callCount, expectedDeletions.length);
           expectedDeletions.forEach(
             item => assert.isTrue(fakeCache.delete.calledWith(item[0]))
           );

           var expectedFetches = order.addnew.concat(order.update);
           assert.equal(self.fetch.callCount, expectedFetches.length);
           expectedFetches.forEach(
             item => assert.isTrue(self.fetch.calledWith(item[0]))
           );

           var expectedPuts = expectedFetches;
           assert.equal(fakeCache.put.callCount, expectedPuts.length);
           expectedPuts.forEach(
             item => assert.isTrue(fakeCache.put.calledWith(item[0], networkResponse))
           );
         });
       });
  });

});
