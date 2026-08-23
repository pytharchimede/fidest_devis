self.addEventListener('notificationclick', event => {
  event.notification.close();
  const url = event.notification.data && event.notification.data.url ? event.notification.data.url : 'dashboard.php';
  const key = event.notification.data && event.notification.data.key ? event.notification.data.key : '';
  const markRead = key ? fetch('request/notifications.php', {method:'POST',headers:{'Content-Type':'application/json'},credentials:'include',body:JSON.stringify({key,dismiss:false})}).catch(()=>null) : Promise.resolve();
  event.waitUntil(markRead.then(() => clients.matchAll({type:'window',includeUncontrolled:true})).then(windows => {
    for (const client of windows) { if ('focus' in client) { client.navigate(url); return client.focus(); } }
    return clients.openWindow(url);
  }));
});
