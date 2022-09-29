importScripts("https://www.gstatic.com/firebasejs/7.9.1/firebase-app.js")
importScripts("https://www.gstatic.com/firebasejs/7.9.1/firebase-messaging.js")

var firebaseConfig = {
    apiKey: "AIzaSyAzF4LwExbssw-d3APPFgUz1RsQHFLwluE",
    authDomain: "evntoomap.firebaseapp.com",
    projectId: "evntoomap",
    storageBucket: "evntoomap.appspot.com",
    messagingSenderId: "408472667738",
    appId: "1:408472667738:web:b0fae95ae26ad784d17a63",
    measurementId: "G-EDMQ72LN2W"
};

firebase.initializeApp(firebaseConfig);

// const messaging = firebase.messaging();

// messaging.onBackgroundMessage((payload) => {
//     console.log('[firebase-messaging-sw.js] Received background message ', payload);
//     // Customize notification here
//     const { title, body } = payload.notification;

//     const notificationTitle = 'Background Message Title';
//     const notificationOptions = {
//         body
//     };

//     self.registration.showNotification(notificationTitle,
//         notificationOptions);
// });