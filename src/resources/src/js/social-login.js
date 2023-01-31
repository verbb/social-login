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
        this.renderedLogin = false;
        this.html = settings.html;

        this.bindSubmitButtons();

        const $form = $('#login #login-form');

        // Setup regular login form
        if ($form.length) {
            this.renderLoginForm($form);
        }

        // Setup session-ended login form. More involved becuase it's triggered via JS
        // So we need to watch for the dynamically-added element
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(addedNode) {
                    if (addedNode.id === 'loginmodal') {
                        self.renderLoginModalForm(addedNode);
                    }
                });
            });
        });

        observer.observe(document.body, { childList: true, subtree: true });
    },

    renderLoginForm($form) {
        $(this.html).insertAfter($form);
    },

    renderLoginModalForm(form) {
        const $loginModal = $(form);
        const $wrapper = $loginModal.find('.body');

        // Only insert it once, as due to session-pinging, this can fire multiple times
        if (this.renderedLogin) {
            return;
        }

        $(this.html).insertAfter($wrapper);

        // Resize the modal to fit
        $loginModal.trigger('updateSizeAndPosition');
        $(window).trigger('resize');

        this.renderedLogin = true;
    },

    bindSubmitButtons() {
        // `click` doesn't seem to work in the login modal...
        $(document).on('mouseup', 'button[data-social-provider]', function(e) {
            e.preventDefault();

            let $btn = $(e.currentTarget);
            let $form = $('form#x');

            Craft.submitForm($form, {
                action: 'social-login/auth/login',
                redirect: null,
                params: {
                    loginName: Craft.username,
                    provider: $btn.data('social-provider'),
                },
            });
        });
    },
});

})(jQuery);
