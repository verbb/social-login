// ==========================================================================

// Social Login Plugin for Craft CMS
// Author: Verbb - https://verbb.io/

// ==========================================================================

if (typeof Craft.SocialLogin === typeof undefined) {
    Craft.SocialLogin = {};
}

(function($) {

Craft.SocialLogin.CpLoginForm = Garnish.Base.extend({
    init: function(settings) {
        const self = this;

        this.html = '<div class="social-login-cp-container">' + settings.html + '</div>';

        this.bindSubmitButtons();

        const $form = $('.login-form-container');

        // Setup regular login form
        if ($form.length) {
            this.renderLoginForm($form);
        }

        // Setup session-ended login form. More involved becuase it's triggered via JS
        // So we need to watch for the dynamically-added element.
        // Note: Craft's elevated-session ("Confirm your identity") modal shares the same
        // `modal login-modal fitted` classes, but SSO cannot satisfy password elevation.
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(addedNode) {
                    if (self.hasClasses(addedNode, ['modal', 'login-modal', 'fitted'])) {
                        self.renderLoginModalForm(addedNode);
                    }
                });
            });
        });

        observer.observe(document.body, { childList: true, subtree: true });
    },

    renderLoginForm($form) {
        // Only insert it once, as due to session-pinging, this can fire multiple times
        if ($('.social-login-cp-container').length) {
            return;
        }

        $(this.html).insertAfter($form);
    },

    renderLoginModalForm(form) {
        const $loginModal = $(form);
        const $wrapper = $loginModal.find('.body .login-modal-form .login-container');

        // Skip elevated-session reauth; keep SSO for session-expired re-login only.
        // The "Keep me signed in" warning uses different classes and is ignored above.
        if (this.isElevatedSessionModal($loginModal)) {
            return;
        }

        // Only insert it once, as due to session-pinging, this can fire multiple times
        if ($('.social-login-cp-container').length) {
            return;
        }

        $(this.html).insertAfter($wrapper);

        // Resize the modal to fit
        $loginModal.trigger('updateSizeAndPosition');
        $(window).trigger('resize');
    },

    isElevatedSessionModal($loginModal) {
        if (Craft.elevatedSessionManager && Craft.elevatedSessionManager.showingLoginModal) {
            return true;
        }

        // Fallback for translated heading Craft renders for elevated reauth
        const heading = $loginModal.find('.login-modal-intro h1').text().trim();

        return heading === Craft.t('app', 'Confirm your identity.');
    },

    bindSubmitButtons() {
        const self = this;

        // `click` doesn't seem to work in the login modal...
        $(document).on('mouseup', 'button[data-social-provider]', async function(e) {
            e.preventDefault();

            let $btn = $(e.currentTarget);
            let $form = $('form#x');

            // Ensure that we ping the session endpoint again to get a valid CSRF token, 
            // as the previous session has ended, and the current token is invalid.
            const { data } = await Craft.sendActionRequest('GET', 'users/session-info');

            const payload = {
                action: 'social-login/auth/login',
                redirect: null,
                params: {
                    loginName: Craft.username,
                    provider: $btn.data('social-provider'),
                },
            };

            if (self.getRememberMe($btn)) {
                payload.params.rememberMe = 1;
            }

            payload.params[data.csrfTokenName] = data.csrfTokenValue;

            Craft.submitForm($form, payload);
        });
    },

    getRememberMe($btn) {
        // Craft's CP checkbox uses class `login-remember-me` without a name.
        // Front-end (and some custom CP templates) use `name="rememberMe"`.
        const $checkbox = $btn
            .closest('.login-container, .login-modal-form, body')
            .find('.login-remember-me, input[name="rememberMe"]')
            .filter(':checkbox')
            .first();

        return $checkbox.length ? !!$checkbox.prop('checked') : false;
    },

    hasClasses(element, classes) {
        if (!element || !element.classList) {
            return false;
        }

        return classes.every(cls => element.classList.contains(cls));
    },

});

})(jQuery);
