describe('onFetch()', function() {
  'use strict';

  var fakeEvent;
  var fakeResponse = {};

  beforeEach(function() {
    fakeEvent = {
      request: {},
      respondWith: sinon.stub()
    };
    importScripts('/base/wp-offline-content/lib/js/content-sw.js');
    wpOfflineContent.get = sinon.stub().returns(fakeResponse);
  });


  it('do not respond if excluded', function() {
    sinon.stub(wpOfflineContent, 'shouldBeHandled').returns(false);
    wpOfflineContent.onFetch(fakeEvent);
    assert.isFalse(fakeEvent.respondWith.called);
  });

  it('respond if not excluded', function() {
    sinon.stub(wpOfflineContent, 'shouldBeHandled').returns(true);
    wpOfflineContent.onFetch(fakeEvent);
    assert.isTrue(fakeEvent.respondWith.calledOnce);
    assert.isTrue(fakeEvent.respondWith.calledWith(fakeResponse));
  });
});
