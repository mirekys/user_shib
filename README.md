# User Shib

This application enables federated Shibboleth
authentication and automatic user and group provisioning
based on Shibboleth attributes. It requires
a configured and running Shibboleth SP.

# Shibboleth configuration

You can configure a Shibboleth SP by following this official [guide](https://wiki.shibboleth.net/confluence/display/SHIB2/NativeSPGettingStarted).

# Apache configuration

In order to get the authentication backend working you
must enforce Shibboleth session at least on the following Location:

```
<Location /owncloud/index.php/apps/user_shib/login>
	AuthType shibboleth
	ShibRequireSession On
	ShibUseHeaders Off
	ShibExportAssertion On
	ShibRequestSetting requireSession 1
</Location>
```

Since we establish a proper ownCloud authenticated session on the login URL above,
the rest of the ownCloud could be covered by a Shibboleth [lazy session](https://aai-demo.switch.ch/lazy/),
We rely on the ownCloud here to determine, if it needs authentication or not.

```
<Location /owncloud>
	...
	AuthType shibboleth
	Require shibboleth
	ShibUseHeaders Off
	ShibExportAssertion On
</Location>
```

# App configuration

As of now, you can install the app by just putting it inside your _apps/_ directory
and enabling it, like you would with any other app.

## Admin configuration

On the _Admin_ page, you can configure mapping of Shibboleth attributes
to ownCloud and some backend options. The meaning of each option is following:

### Mapping configuration

* **Attribute prefix** - prefix for all attributes provided by Shibboleth (aka _attributePrefix_ Shibboleth setting).
* **Username** - attribute to be used for ownCloud user name.
* **Full Name** - attribute to be used for display name.
* **First Name** - alternative attribute to be used for display name.
* **Surname** - alternative attribute to be used for display name.
* **Email** - attribute to be used as contact e-mail address.
* **Groups** - attribute to be used for group assignment [_not implemented yet_].
* **External identities** - known external identities of the user (e.g. Google, FB, github) [_not implemented yet_].
* **Required (checkbox)** - when checked, users must provide this attribute in order to be logged in

### Backend configuration

* **Backend Activated** - Disabling it disables authentication using this user backend, but keeps everything else in place.
* **Autocreate accounts** - Create new account on user's first login.
* **Update user info on login** - Updates user's mail, display name, last seen and other metadata on each login.
* **Update identity mappings on login** - Updates mapping of user's external identities to an ownCloud account on each login (_requires External identities field_).
* **Protected Groups** - Do not override this OC groups by Shibboleth attribute _Groups_ [_not implemented yet_].

## Personal configuration

Users are required to set a special password for the synchronization clients on their _Personal_ page
under _Client login credentials_ section.
