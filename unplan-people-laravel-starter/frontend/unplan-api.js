/* UNPLAN People API helper. Load after your authenticated login flow.
   Do not store a permanent token in source code or commit credentials. */
(function (global) {
  'use strict';

  const API_BASE = (global.UNPLAN_API_BASE || 'https://api.example.com/api').replace(/\/+$/, '');
  let token = null;

  async function request(path, options = {}) {
    if (!token) throw new Error('Please sign in before using UNPLAN People.');
    const response = await fetch(`${API_BASE}${path}`, {
      ...options,
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        Authorization: `Bearer ${token}`,
        ...(options.headers || {})
      }
    });

    const payload = await response.json().catch(() => ({}));
    if (!response.ok) {
      const message = payload.message || `Request failed (${response.status})`;
      throw new Error(message);
    }
    return payload;
  }

  function getCurrentPosition() {
    return new Promise((resolve, reject) => {
      if (!navigator.geolocation) return reject(new Error('GPS is not supported by this browser.'));
      navigator.geolocation.getCurrentPosition(
        p => resolve({ latitude: p.coords.latitude, longitude: p.coords.longitude }),
        () => reject(new Error('Location permission is required for GPS attendance.')),
        { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
      );
    });
  }

  global.UnplanAPI = {
    setToken(value) { token = value; },
    clearToken() { token = null; },
    getMe: () => request('/me'),
    getDashboard: () => request('/dashboard'),
    getAttendance: () => request('/attendance'),
    async checkIn() {
      const coords = await getCurrentPosition();
      return request('/attendance/check-in', { method: 'POST', body: JSON.stringify(coords) });
    },
    async checkOut() {
      const coords = await getCurrentPosition();
      return request('/attendance/check-out', { method: 'POST', body: JSON.stringify(coords) });
    },
    getLeaves: () => request('/leaves'),
    submitLeave(data) {
      return request('/leaves', { method: 'POST', body: JSON.stringify(data) });
    }
  };
})(window);
