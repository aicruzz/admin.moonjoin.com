importScripts('https://www.gstatic.com/firebasejs/8.3.2/firebase-app.js');
importScripts('https://www.gstatic.com/firebasejs/8.3.2/firebase-messaging.js');

firebase.initializeApp({
    apiKey: "AIzaSyBGEnFwuoMTj1dmAcgf4tlkWC6A8txWE3c",
    authDomain: "moonjoin-870e5.firebaseapp.com",
    projectId: "moonjoin-870e5",
    storageBucket: "moonjoin-870e5.firebasestorage.app",
    messagingSenderId: "423147747880",
    appId: "1:423147747880:web:285792fec0541c34df931d",
    measurementId: "G-Y91YZJ6LTP"
});

const messaging = firebase.messaging();
messaging.setBackgroundMessageHandler(function (payload) {
    return self.registration.showNotification(payload.data.title, {
        body: payload.data.body ? payload.data.body : '',
        icon: payload.data.icon ? payload.data.icon : ''
    });
});