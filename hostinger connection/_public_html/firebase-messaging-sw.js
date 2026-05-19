importScripts('https://www.gstatic.com/firebasejs/10.12.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.12.0/firebase-messaging-compat.js');

firebase.initializeApp({
    apiKey: "AIzaSyBAzDxElpLX--lJ8xnvCrQBP-zYFMW_QLQ",
    authDomain: "arigato-devan-prompts.firebaseapp.com",
    projectId: "arigato-devan-prompts",
    storageBucket: "arigato-devan-prompts.firebasestorage.app",
    messagingSenderId: "770814780270",
    appId: "1:770814780270:web:03e1cd5de780452217d77f"
});

const messaging = firebase.messaging();

messaging.onBackgroundMessage(function(payload) {
    const notif = payload.notification || {};
    self.registration.showNotification(notif.title || '✨ New Prompt!', {
        body: notif.body || 'A new AI couple prompt just dropped!',
        icon: '/toplogo/logo01.webp',
        badge: '/favicon/favicon-32x32.png',
        data: { url: (payload.data && payload.data.url) || 'https://arigatodevan.com' }
    });
});

self.addEventListener('notificationclick', function(event) {
    event.notification.close();
    var url = (event.notification.data && event.notification.data.url) || 'https://arigatodevan.com';
    event.waitUntil(clients.openWindow(url));
});
