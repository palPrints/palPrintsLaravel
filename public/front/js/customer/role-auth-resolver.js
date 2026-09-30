(function publishPalprintsRoleAuth(window, document) {
  "use strict";

  const ROLES = new Set(["customer", "designer"]);
  const DEVELOPMENT_ROLE_KEY = "palprints-user-role";

  function isDevelopmentEnvironment() {
    return window.location.protocol === "file:"
      || ["localhost", "127.0.0.1", "[::1]"].includes(window.location.hostname);
  }

  async function readAuthenticatedRole() {
    const provider = window.PALPRINTS_AUTH;
    if (provider) {
      try {
        const user = typeof provider.getCurrentUser === "function"
          ? await provider.getCurrentUser()
          : provider.currentUser || provider.user || null;
        const role = user?.role || provider.role || null;
        return { configured: true, role: ROLES.has(role) ? role : null };
      } catch (error) {
        console.error("Unable to resolve the authenticated PALPRINTS user.", error);
        return { configured: true, role: null };
      }
    }

    if (document.body.dataset.authenticated === "false") return { configured: true, role: null };
    if (ROLES.has(document.body.dataset.userRole)) {
      return { configured: true, role: document.body.dataset.userRole };
    }
    return { configured: false, role: null };
  }

  async function resolve(options = {}) {
    const authenticated = await readAuthenticatedRole();
    if (authenticated.configured) {
      return { role: authenticated.role, source: "authenticated", development: false };
    }

    const development = isDevelopmentEnvironment();
    if (!development) return { role: null, source: "unauthenticated", development: false };

    const requestedRole = new URLSearchParams(window.location.search).get("role");
    if (ROLES.has(requestedRole)) return { role: requestedRole, source: "development-query", development: true };
    if (ROLES.has(options.contextRole)) return { role: options.contextRole, source: "development-context", development: true };

    try {
      const storedRole = window.localStorage.getItem(DEVELOPMENT_ROLE_KEY);
      if (ROLES.has(storedRole)) return { role: storedRole, source: "development-storage", development: true };
    } catch (error) { /* Development storage is optional. */ }

    if (options.developmentDefault && ROLES.has(options.developmentDefault)) {
      return { role: options.developmentDefault, source: "development-default", development: true };
    }
    return { role: null, source: "unauthenticated", development: true };
  }

  function redirectToLogin() {
    const loginUrl = new URL("/login", window.location.origin);
    loginUrl.searchParams.set("returnTo", window.location.pathname + window.location.search + window.location.hash);
    window.location.replace(loginUrl.href);
  }

  window.PALPRINTS_ROLE_AUTH = Object.freeze({ resolve, redirectToLogin, isDevelopmentEnvironment });
})(window, document);
