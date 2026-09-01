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

        this.settings = $.extend({
            enableCpElevatedLogin: false,
        }, settings);

        this.html = '<div class="social-login-cp-container">' + this.settings.html + '</div>';

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
        // Only inject into the session-expired re-login modal.
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
        const self = this;
        const $loginModal = $(form);

        // Craft finishes modal setup (flags, intro copy, LoginForm) across the same turn /
        // fade-in. Evaluate immediately and again on the next frame so we don't inject into
        // elevated-session / MFA screens, and so we can strip a premature insert.
        const tryRender = function() {
            const isElevated = self.isElevatedSessionModal($loginModal);

            if (isElevated && !self.settings.enableCpElevatedLogin) {
                self.removeSocialLoginFromModal($loginModal);
                self.watchElevatedModal($loginModal);
                return;
            }

            // Session-expired re-login always supports SSO; elevated reauth is opt-in via config.
            if (!isElevated && !self.isSessionEndedModal($loginModal)) {
                return;
            }

            if ($loginModal.find('.social-login-cp-container').length) {
                // Already injected (e.g. previous rAF); still remeasure in case Craft sized first.
                self.resizeModal($loginModal);
                return;
            }

            const $wrapper = $loginModal.find('.body .login-modal-form .login-container');

            if (!$wrapper.length) {
                return;
            }

            $(self.html).insertAfter($wrapper);
            self.resizeModal($loginModal);
            self.bindModalResize($loginModal);
        };

        tryRender();
        requestAnimationFrame(tryRender);
    },

    removeSocialLoginFromModal($loginModal) {
        const $sso = $loginModal.find('.social-login-cp-container');

        if (!$sso.length) {
            return;
        }

        $sso.remove();
        this.resizeModal($loginModal);
    },

    /**
     * Garnish.Modal locks an explicit height during show()/fadeIn. Triggering a jQuery
     * "updateSizeAndPosition" event on the DOM node is a no-op — call the Modal instance
     * (same pattern as Craft.LoginForm.onResize) so session-expired SSO isn't clipped.
     */
    resizeModal($loginModal) {
        const modal = $loginModal.data('modal');

        if (!modal || typeof modal.updateSizeAndPosition !== 'function') {
            if (typeof Garnish !== 'undefined' && Garnish.$win) {
                Garnish.$win.trigger('resize');
            } else {
                $(window).trigger('resize');
            }
            return;
        }

        const update = function() {
            modal.updateSizeAndPosition();
        };

        if (typeof Garnish !== 'undefined' && Garnish.requestAnimationFrame) {
            Garnish.requestAnimationFrame(update);
        } else {
            requestAnimationFrame(update);
        }
    },

    bindModalResize($loginModal) {
        if ($loginModal.data('socialLoginResizeBound')) {
            return;
        }

        const modal = $loginModal.data('modal');

        if (!modal || typeof modal.on !== 'function') {
            return;
        }

        $loginModal.data('socialLoginResizeBound', true);

        const self = this;

        // show() measures before our MutationObserver runs; fadeIn remeasures afterward.
        modal.on('fadeIn', function() {
            self.resizeModal($loginModal);
        });
    },

    watchElevatedModal($loginModal) {
        if (!$loginModal.length || $loginModal.data('socialLoginElevatedWatch')) {
            return;
        }

        $loginModal.data('socialLoginElevatedWatch', true);

        const self = this;
        const observer = new MutationObserver(function() {
            self.removeSocialLoginFromModal($loginModal);
        });

        observer.observe($loginModal.get(0), { childList: true, subtree: true });
    },

    isElevatedSessionModal($loginModal) {
        if (Craft.elevatedSessionManager && Craft.elevatedSessionManager.showingLoginModal) {
            return true;
        }

        // Fallback for translated heading Craft renders for elevated reauth
        return this.modalHeadingEquals($loginModal, Craft.t('app', 'Confirm your identity.'));
    },

    isSessionEndedModal($loginModal) {
        // Session-expired modal may use widont (nbsp before last word)
        return this.modalHeadingEquals($loginModal, Craft.t('app', 'Your session has ended.'));
    },

    modalHeadingEquals($loginModal, expected) {
        const heading = $loginModal.find('.login-modal-intro h1').text().replace(/\s+/g, ' ').trim();
        const normalized = String(expected || '').replace(/\s+/g, ' ').trim();

        return !!heading && heading === normalized;
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
