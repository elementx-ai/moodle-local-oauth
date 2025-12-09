# OAuth2 Server Plugin for Moodle

It provides an [OAuth2](https://tools.ietf.org/html/rfc6749 "RFC6749") server so that a user can use its Moodle account to log in to your application.
Oauth2 Library has been taken from https://github.com/bshaffer/oauth2-server-php

## Requirements
* #### Moodle 4.5 or higher
* #### Admin account

## Installation steps
1. Download the latest [release](https://github.com/elementx-ai/moodle-local-oauth/releases) _.zip_ file.

2. Log in to Moodle as an administrator.

3. Search for a block named _Administration_ and look for _Site Administration > Plugins > Install Plugins_.

4. Choose the _.zip_ file and hit the button _Install Plugin from the ZIP file_.

5. Make sure the directory *path_to_moodle/local/* has writing permissions for moodle. If the validation is ok, install it.

6. Go to *Site Administration > Server > OAuth provider settings*

7. Click *Add new client*

8. Fill in the form. Your Client Identifier and Client Secret (which will be given later) will be used for you to authenticate. The Redirect URL must be the URL mapping to your client that will be used.

## How to use

1. From your application, redirect the user to this URL: `http://moodledomain.com/local/oauth/login.php?client_id=EXAMPLE&response_type=code` *(remember to replace the URL domain with the domain of Moodle and replace EXAMPLE with the Client Identifier given in the form.)*

2. The user must log in to Moodle and authorize your application to use its basic info.

3. If everything worked correctly, the plugin should redirect the user to something like: `http://yourapplicationdomain.com/foo?code=55c057549f29c428066cbbd67ca6b17099cb1a9e` *(that's a GET request to the Redirect URL given with the code parameter)*

4. Using the code given, your application must send a POST request to `http://moodledomain.com/local/oauth/token.php`  having the following parameters: `{'code': '55c057549f29c428066cbbd67ca6b17099cb1a9e', 'client_id': 'EXAMPLE', 'client_secret': 'codeGivenAfterTheFormWasFilled', 'grant_type': 'authorization_code',   'scope': 'user_info'}`.

5. If the correct credentials were given, the response should a JSON be like this: `{"access_token":"79d687a0ea4910c6662b2e38116528fdcd65f0d1","expires_in":3600,"token_type":"Bearer","scope":"user_info","refresh_token":"c1de730eef1b2072b48799000ec7cde4ea6d2af0"}`

6. Finally, send a POST request to `http://moodledomain.com/local/oauth/user_info.php` passing the access token in the body as `application/x-www-form-urlencoded`, like: `access_token:79d687a0ea4910c6662b2e38116528fdcd65f0d1`.

7. If the token given is valid, a JSON containing the user information is returned. Ex: `{"id":"22","username":"foobar","idnumber":"","firstname":"Foo","lastname":"Bar","email":"foo@bar.com","lang":"en","phone1":"5551619192","auth":"manual","country":"foo","description":"bar"}`

Note: If testing in Postman, you need to set encoding to `x-www-form-urlencoded` for POST requests.

## Development

The easiest way to develop this plugin is to run Moodle locally using [moodle-docker](https://github.com/moodlehq/moodle-docker).

1. Set up your local Moodle environment following the instructions in the `moodle-docker` repository.
2. Navigate to your Moodle installation's `local` directory (e.g., `moodle/public/local`).
3. Clone this repository into a folder named `oauth`:

```sh
cd moodle/public/local
git clone git@github.com:elementx-ai/moodle-local-oauth.git oauth
```

4. Start your Moodle docker container. The plugin will be available for installation or upgrade.

**This plugin has been tested on Moodle 4.5+**
