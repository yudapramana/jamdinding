// /**
//  * We'll load the axios HTTP library which allows us to easily issue requests
//  * to our Laravel back-end. This library automatically handles sending the
//  * CSRF token as a header based on the value of the "XSRF" token cookie.
//  */

// import axios from 'axios';
// window.axios = axios;

// import jquery from 'jquery';
// window.$ = window.jQuery = jquery;
// window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// // ambil CSRF token dari meta
// const token = document
//   .querySelector('meta[name="csrf-token"]')
//   ?.getAttribute('content')

// if (token) {
//   axios.defaults.headers.common['X-CSRF-TOKEN'] = token
// }
// /**
//  * Echo exposes an expressive API for subscribing to channels and listening
//  * for events that are broadcast by Laravel. Echo and event broadcasting
//  * allows your team to easily build robust real-time web applications.
//  */

// // import Echo from 'laravel-echo';

// // import Pusher from 'pusher-js';
// // window.Pusher = Pusher;

// // window.Echo = new Echo({
// //     broadcaster: 'pusher',
// //     key: import.meta.env.VITE_PUSHER_APP_KEY,
// //     cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
// //     wsHost: import.meta.env.VITE_PUSHER_HOST ? import.meta.env.VITE_PUSHER_HOST : `ws-${import.meta.env.VITE_PUSHER_APP_CLUSTER}.pusher.com`,
// //     wsPort: import.meta.env.VITE_PUSHER_PORT ?? 80,
// //     wssPort: import.meta.env.VITE_PUSHER_PORT ?? 443,
// //     forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
// //     enabledTransports: ['ws', 'wss'],
// // });


import axios from 'axios'

// ===============================
// AXIOS GLOBAL CONFIG
// ===============================
axios.defaults.withCredentials = true
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'

import jquery from 'jquery';
window.$ = window.jQuery = jquery;

// ===============================
// CSRF TOKEN
// ===============================
const token = document
  .querySelector('meta[name="csrf-token"]')
  ?.getAttribute('content')

if (token) {
  axios.defaults.headers.common['X-CSRF-TOKEN'] = token
}

// ===============================
// RESPONSE INTERCEPTOR
// ===============================
axios.interceptors.response.use(
  response => response,
  error => {
    const status = error.response?.status

    // CSRF expired / session expired
    if (status === 419) {
      alert('Session kedaluwarsa. Silakan login ulang.')
      window.location.href = '/login'
    }

    // Unauthorized
    if (status === 401) {
      window.location.href = '/login'
    }

    return Promise.reject(error)
  }
)

export default axios
