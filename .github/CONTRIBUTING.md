CONTRIBUTING
============

Any contribution is welcome and encouraged.

Issues
------

Reporting an issue is the foremost way to contribute.

When reporting an issue, try to give as much details as possible. Ability to reproduce the issue is a priority.
If you give an example that reproduces your issue you will have more chances to get it fixed.

Those details include: 

- php version
- chrome version

Also always try to isolate the issue in an environment easy to reproduce with as few steps as possible.

### Providing a trace of the application execution

Once you isolated the issue you can enable the debug mode of the library. That is achieved by adding the option "debugLogger" in the browser factory.

```php
    $browser = $browserFactory->createBrowser([
        'debugLogger'     => 'php://stdout'
    ]);
```

You will be provided with an output of what is happening within the library and that can help to figure out what is causing the issue.

Tests
-----

Writting test is also a great way to contribute because it ensures that the library will remain consistent after any upgrade.

Implementing new features or fixing bugs
----------------------------------------

Implementing new features will allow anyone to take profit of your work. Just remember to rise an issue and discuss it before to make sure that the work goes in the right direction and you work will be approved.

In addition all contributions must be tested following as much as possible the current test structure:
- One class = one test file in ``test/suites`` and the class must be annotated with ``@covers``.
- One class method = one method in the test class.

Look at current tests in ``test/suites`` for more details.

Writting documentation
----------------------

We encourage anyone to improve the documentation by adding new example or by fixing current one that would be wrong or outdated.
