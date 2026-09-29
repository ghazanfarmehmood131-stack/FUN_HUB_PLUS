/**
 * Client Authentication Simulation
 * Persists session and profile locally without external dependencies.
 */

window.AuthService = (function() {
  const STORAGE_KEY = window.APP_CONFIG.STORAGE_USER_KEY;

  function getUser() {
    try {
      const data = localStorage.getItem(STORAGE_KEY);
      return data ? JSON.parse(data) : null;
    } catch (e) {
      console.error('Error reading auth state', e);
      return null;
    }
  }

  function login(email, password) {
    if (!email || !email.includes('@')) {
      return { success: false, message: 'Please provide a valid email address.' };
    }
    if (!password || password.length < 6) {
      return { success: false, message: 'Password must contain at least 6 characters.' };
    }

    const name = email.split('@')[0];
    const user = {
      email: email.trim().toLowerCase(),
      name: name.charAt(0).toUpperCase() + name.slice(1),
      joined: new Date().toLocaleDateString('en-US', { month: 'short', year: 'numeric' })
    };

    localStorage.setItem(STORAGE_KEY, JSON.stringify(user));
    window.dispatchEvent(new CustomEvent('auth-changed', { detail: { user } }));
    updateAuthNav();
    return { success: true, user };
  }

  function signup(name, email, password) {
    if (!name || name.trim().length < 2) {
      return { success: false, message: 'Please provide your name.' };
    }
    if (!email || !email.includes('@')) {
      return { success: false, message: 'Please provide a valid email address.' };
    }
    if (!password || password.length < 6) {
      return { success: false, message: 'Password must contain at least 6 characters.' };
    }

    const user = {
      email: email.trim().toLowerCase(),
      name: name.trim(),
      joined: new Date().toLocaleDateString('en-US', { month: 'short', year: 'numeric' })
    };

    localStorage.setItem(STORAGE_KEY, JSON.stringify(user));
    window.dispatchEvent(new CustomEvent('auth-changed', { detail: { user } }));
    updateAuthNav();
    return { success: true, user };
  }

  function logout() {
    localStorage.removeItem(STORAGE_KEY);
    window.dispatchEvent(new CustomEvent('auth-changed', { detail: { user: null } }));
    updateAuthNav();
  }

  function updateAuthNav() {
    const user = getUser();
    const navAuth = document.getElementById('navAuthButton');
    if (!navAuth) return;

    if (user) {
      navAuth.textContent = user.name.toUpperCase();
      navAuth.href = 'auth.php';
      navAuth.title = `Signed in as ${user.email}. Click to view account.`;
    } else {
      navAuth.textContent = 'SIGN IN';
      navAuth.href = 'auth.php';
      navAuth.title = 'Sign in or register';
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    updateAuthNav();
  });

  return {
    getUser,
    login,
    signup,
    logout,
    updateAuthNav
  };
})();
