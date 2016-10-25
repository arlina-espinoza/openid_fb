Open ID Connect Facebook Client
===============================

This is a Drupal 8 module that extends the [OpenID Connect module]
(https://www.drupal.org/project/openid_connect) with a _Facebook_ client that works around the [_Facebook Login_ flow](https://developers.facebook.com/docs/facebook-login/manually-build-a-login-flow).

Some extra functionality is also provided:

- Social login landing page, where the user is presented with a list of all OpenID login options (/user/login/sso).
- Redirect "access denied" pages to the social login landing page.
- Change Drupal's default "/user/login" path to "/non-sso-login". 

This code was developed as part of session ["Deep dive into D8 through SSO example"](https://goo.gl/kuQ5f8),
to illustrate design patterns in Drupal 8, such as plugins, route subscribers and event listeners.
