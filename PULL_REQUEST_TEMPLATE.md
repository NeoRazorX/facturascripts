# Pull Request Template

## Description

<!---NOTE: All links like this are comments, no need to be removed.--->
<!---Please include a summary of the change.--->
<!---If solves an open issue, link to it including: Fixes # (issue)--->

Your PR description goes here.


## Type of change

<!---Please delete options that are not relevant.--->

- `Fixed` (non-breaking change which fixes an issue)
- `Added` (non-breaking change which adds functionality)
- `Breaking change` (fix or feature that would cause existing functionality to not work as expected)
- `Changed` (this change requires a documentation update)
- `Obsolete` (this change require triggers to notify use of methods that will be deleted or replaced)
- `Deleted` (this change remove old code that previously must be marked as obsolete)
- `Security` (this change implies that a security issue was fixed)


## How has this been tested?

<!---Replace `[ ]` with `[X]` to mark what you do in the next list.--->
<!---Please describe the additional tests that you ran to verify your changes. Provide instructions so we can reproduce. Please also list any relevant details for your custom test configuration.--->

- [ ] Clean database
- [ ] Database with random data
- [ ] Checked with `vendor/bin/phpcbf --tab-width=4 --encoding=utf-8 --standard=phpcs.xml Core -s` before submit
- [ ] Checked with `vendor/bin/phpcs --tab-width=4 --encoding=utf-8 --standard=phpcs.xml Core -s` before submit
- [ ] Checked with `vendor/bin/phpunit --configuration phpunit.xml` before submit
<!---- [ ] If additional tests was realized, added here--->


## Checklist:

<!---Replace `[ ]` with `[X]` to mark what you do in the next list.--->
<!---Please add additional points if needed.--->

- [ ] My code follows the [style guidelines](https://github.com/NeoRazorX/facturascripts/blob/master/CONTRIBUTING.md#pull-requests-peticiones-para-incorporar-cambios) of this project. At least [PSR-1](http://www.php-fig.org/psr/psr-1/) and [PSR-2](http://www.php-fig.org/psr/psr-2/)
- [ ] I have performed a self-review of my own code
- [ ] I have commented my code, particularly in hard-to-understand areas
- [ ] I have made corresponding changes to the documentation
- [ ] My changes generate no new warnings
- [ ] I have added tests that prove my fix is effective or that my feature works
- [ ] New and existing unit tests pass locally with my changes
- [ ] Any dependent changes to external code (as plugins) have been notified to be updated
