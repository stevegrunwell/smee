# Smee

[![Build Status](https://travis-ci.org/stevegrunwell/smee.svg?branch=develop)](https://travis-ci.org/stevegrunwell/smee)

Smee is a Composer package designed to make it easier to share [Git hooks] with a project.


## Why Git hooks?

[Git hooks] are a useful way to automatically perform actions at different points throughout the Git lifecycle. These actions could include verifying coding standards, running unit tests, enforcing commit message formats, and more.

The _downside_ to Git hooks is that they're not distributed with the Git repository, meaning it's difficult to ensure that all developers on a project are using the same hooks.

Smee aims to change that, by introducing the `.githooks` directory and providing an easy way to automatically install the hooks on `composer install`.


## Installation

Smee should be added to your projects via [Composer]:

```sh
$ composer require --dev stevegrunwell/smee
```

To ensure Smee is automatically run for other users, add Smee to the "post-install-cmd" [Composer event]:

```json
{
    "scripts": {
        "post-install-cmd": [
            "smee smee:install"
        ]
    }
}
```

Finally, create a `.githooks` directory in the root of your project, and add the hooks you'd like to use in your project. These files should be committed to the git repository, and will automatically be copied into other developers' local repositories the next time they run `composer install`.


## Available Git hooks

Git supports a number of hooks, which each fire at different points throughout the lifecycle. Each Git hook exists in a separate file, named after the hook it corresponds to (e.g. `.git/hooks/pre-commit` will be executed on the "pre-commit" hook).


### Commit workflow

These are some of the most common Git hooks, firing at different points as a developer attempts to commit code:

<dl>
    <dt>pre-commit</dt>
    <dd>Runs before a commit message is created.</dd>
    <dt>prepare-commit-msg</dt>
    <dd>Run before the commit message editor is opened but after the default message is created.</dd>
    <dt>commit-msg</dt>
    <dd>Runs before saving the commit message, often used for verifying commit message format.</dd>
    <dt>post-commit</dt>
    <dd>Runs after a commit has been made. This hook is typically used for notifications.</dd>
</dl>


### Email workflow

[Git also supports an email-based workflow via `git am`](https://git-scm.com/docs/git-am), which applies patches in sequence from a mailbox. If you're using this workflow, [a few additional hooks are available](https://git-scm.com/book/en/v2/Customizing-Git-Git-Hooks#applypatch-msg).


### Other useful client-side Git hooks

<dl>
    <dt>pre-rebase</dt>
    <dd>Runs before allowing any code to be rebased.</dd>
    <dt>post-rewrite</dt>
    <dd>Runs after rewriting a commit, e.g. <code>git commit --amend</code>.</dd>
    <dt>post-checkout</dt>
    <dd>Runs after a successful <code>git checkout</code> command.</dd>
    <dt>post-merge</dt>
    <dd>Runs after a successful <code>git merge</code> command.</dd>
    <dt>pre-push</dt>
    <dd>Runs before a <code>git push</code> command.</dd>
    <dt>pre-auto-gc</dt>
    <dd>Runs before Git's periodic garbage collection processes.</dd>
</dl>


[Composer]: https://getcomposer.org
[Composer event]: https://getcomposer.org/doc/articles/scripts.md
[Git hooks]: https://git-scm.com/book/en/v2/Customizing-Git-Git-Hooks
