# Mail Redirect

This TYPO3 extension was inspired by ameos_mailredirect, but allows a more flexible redirecting of adresses. E.g. if you use the postfix adress extension (tagging), you can generate addresses dynamically like _mailtest+{local}-{domain}-{tld}@yourdomain.org_.

## Configuration
All configuration takes place in the TYPO3 extension manager. 

The redirect can be enabled for specific User-Agents and/or IPs. If you want to redirect all mails, set the configuration to * for both checks. 

Addresses can be whitelisted e.g. to only send out mails to specific recipients in test cases. The addresses are compared with the php function fnmatch.

## Running the unit tests
```
typo3/sysext/core/bin/typo3 phpunit:run typo3conf/ext/td_mailredirect/Tests/Unit/*
``` 