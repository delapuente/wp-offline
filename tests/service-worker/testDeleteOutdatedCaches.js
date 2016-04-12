describe('deleteOutdatedCaches()', function() {
  'use strict';

  beforeEach(function() {
    importScripts('/base/wp-offline-content/lib/js/content-sw.js');
    sinon.stub(self.caches, 'delete').returns(Promise.resolve());
  });

  afterEach(function() {
    self.caches.keys.restore();
    self.caches.delete.restore();
  });

  var prefix = 'testprefix::';

  [
    [],
    [prefix + 'old1'],
    [prefix + 'old1', prefix + 'old2']
  ].forEach(outdatedSet => {
    it('deletes any prefixed cache distinct than the current cache)',
       function() {
         wpOfflineContent.cacheName = 'currentcache';
         var currentCache = [wpOfflineContent.cacheName];
         var otherCaches = ['othercache1', 'othercache2'];
         var cacheSet = outdatedSet.concat(otherCaches).concat(currentCache);
         sinon.stub(self.caches, 'keys').returns(Promise.resolve(cacheSet));

         return wpOfflineContent.deleteOutdatedCaches(prefix).then(() => {
           assert.equal(self.caches.delete.callCount, outdatedSet.length);
           self.caches.delete.args.forEach((callArgs, index) => {
             assert.equal(callArgs[0], outdatedSet[index]);
           });
         });
       });
  });
});
