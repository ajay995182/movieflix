/* MovieFlix minimal PWA service worker — cache shell only */
var CACHE = 'movieflix-shell-v1';
var ASSETS = [];
self.addEventListener('install', function (e) {
  self.skipWaiting();
});
self.addEventListener('activate', function (e) {
  e.waitUntil(self.clients.claim());
});
self.addEventListener('fetch', function (e) {
  // Network-first; do not aggressively cache video
  if (e.request.method !== 'GET') return;
  var url = e.request.url;
  if (/\.(m3u8|mp4|webm|ts)(\?|$)/i.test(url)) return;
});
