// resources/js/utils/api.js

// Base URL API Laravel kamu
const API_BASE_URL = 'http://127.0.0.1:8000/api';

/**
 * Fungsi untuk mendapatkan header default untuk request API.
 * Ini akan selalu menyertakan Content-Type dan Accept.
 * Jika ada token, akan menyertakan Authorization header.
 * @param {string|null} token - Token otentikasi (opsional).
 * @returns {Headers} Objek Headers.
 */
function getAuthHeaders(token = null) {
    const headers = new Headers({
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    });

    if (token) {
        headers.append('Authorization', token); // Token sudah dalam format 'Bearer <token_value>'
    }

    return headers;
}

/**
 * Mengirim request POST ke API Laravel untuk login.
 * @param {string} email
 * @param {string} password
 * @returns {Promise<Object>} Data response dari API.
 * @throws {Error} Jika login gagal atau ada masalah jaringan.
 */
export async function loginUser(email, password) {
    const response = await fetch(`${API_BASE_URL}/login`, {
        method: 'POST',
        headers: getAuthHeaders(), // Tidak perlu token untuk login
        body: JSON.stringify({ email, password }),
    });

    const data = await response.json();
    if (!response.ok) {
        // Jika response tidak OK (misal 401, 422), throw error agar bisa ditangkap di store
        throw new Error(data.message || 'Login gagal. Silakan coba lagi.');
    }
    return data;
}

// Nanti akan ada fungsi API lain di sini, misal:
// export async function registerUser(userData, token) { ... }
// export async function getInventories(token) { ... }
