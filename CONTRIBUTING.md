CONTRIBUTING
============

Any contribution is welcome and encouraged.

Issues
------

When reporting an issue, try to give as much details as possible. Ability to reproduce the issue is a priority.
If you give an example that reproduces your issue you will have more chances to get it fixed.

Tests
-----

All contributions must be tested following as much as possible the current test structure:
- One class = one test file in ``test/suites`` and the class must be annotated with ``@covers``.
- One class method = one method in the test class.

Look at current tests in ``test/suites`` for more details.

Conding Standards
-----------------

The code follow the PSR-2 coding standards

Tools
-----

- Run test suit: ``composer test``
- Check Coding standards: ``composer cscheck``
- Auto fix standards: ``.composer csfix``
