# Developer Guidelines

General developer guidelines for working in this repository.

## Branches

The special branches on the repository are:

- `main`: The default branch. Represents the current state of the code and is the deployment candidate to production.
- `production`: The code currently deployed to production.
- `legacy`: Represents the current state of legacy Commons code.

There is no set naming convention for other branches. Choose a name that makes sense. Branches should not be prefixed with `feature-` or `hotfix-`. All branches other than the special branches are presumed to be "feature" branches, and hot fixes are merged directly to `main` (or in extreme circumstances to `production`.)

## Creating and merging branches

Significant ongoing feature development should take place on a separate branch. Early in the development process, a pull request should be opened on GitHub for merging that branch back to main in order to track progress. When [merging the pull request](https://docs.github.com/en/pull-requests/collaborating-with-pull-requests/incorporating-changes-from-a-pull-request/merging-a-pull-request#merging-a-pull-request) back to main, use the "Squash and merge" strategy on GitHub. The source branch should then be deleted unless it is one of the "special" branches.

When merging from `main` to `production`, do not open a pull request. Instead, do a fast-forward merge using `git`: `git merge --ff-only origin/main`. As commits should (almost) never be made directly to `production`, it should always be possible to fast forward unless something has gone wrong.

## Making commits

- Try to make [atomic commits](https://dev.to/samuelfaure/how-atomic-git-commits-dramatically-increased-my-productivity-and-will-increase-yours-too-4a84): Commits that make a single describable change to the code.
- Commit messages should be concise and describe what was changed in a way that another developer can easily understand. 
- When a commit addresses an issue, it should be prefixed with the issue number like this: `#12345` so that it can be auto-linked on GitHub. If it addresses an issue in another repository it should be [prefixed accordingly](https://docs.github.com/en/get-started/writing-on-github/working-with-advanced-formatting/autolinked-references-and-urls#issues-and-pull-requests).

## Coding Style

In general, we follow the [WordPress coding standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/).

Exceptions:
- Use short array syntax ( `[ 1, 2, 3 ]` ) rather than long array syntax ( `array( 1, 2, 3)` ). Short array syntax is much easier to read for complex arrays or when passed as function parameters.
- Name class files in UpperCamelCase (`MyClass.php`) rather than by prefixing with class- (`class-myclass.php`). This follows the more common convention in PHP.

Additionally:
- While WordPress emphasizes backwards compatibility, we require PHP 8+. Make free use of PHP 8 features such as type hints and named parameters.

## Documentation

- Follow the [WordPress documentation](https://developer.wordpress.org/coding-standards/inline-documentation-standards/php/) standards. In particular, every file should have a documentation block explaining its purpose, as should every class and function.
- Documentation *within* functions should be kept to a minimum. Try to write "self-documenting" code that is easy for other developers to understand. If code seems like it needs explanation, first try to rewrite it for clarity.
- Use constants to name hard-coded configurations. This makes code easier to understand and maintain.

## Namespaces

Use [namespaces](https://www.php.net/manual/en/language.namespaces.rationale.php) for isolating names, not classes. Namespaces should be prefixed with `MESHResearch\` but otherwise can follow whatever makes sense in context.



