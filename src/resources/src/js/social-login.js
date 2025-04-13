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
        // So we need to watch for the dynamically-added element
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

        // Easy debug for elevated session login
        // setTimeout(function() {
        //     Craft.elevatedSessionManager.showLoginModal();
        // }, 2000)
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

        // Only insert it once, as due to session-pinging, this can fire multiple times
        if ($('.social-login-cp-container').length) {
            return;
        }

        $(this.html).insertAfter($wrapper);

        // Resize the modal to fit
        $loginModal.trigger('updateSizeAndPosition');
        $(window).trigger('resize');
    },

    bindSubmitButtons() {
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

            payload.params[data.csrfTokenName] = data.csrfTokenValue;

            Craft.submitForm($form, payload);
        });
    },

    hasClasses(element, classes) {
        if (!element || !element.classList) {
            return false;
        }

        return classes.every(cls => element.classList.contains(cls));
    },

});

})(jQuery);
