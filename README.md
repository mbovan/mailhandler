<snippet>
    <content>
# Mailhandler

[![Travis](https://img.shields.io/travis/fantastic91/mailhandler.svg?maxAge=2592000)](https://travis-ci.org/fantastic91/mailhandler/)
[![Twitter](https://img.shields.io/twitter/url/https/github.com/fantastic91/mailhandler.svg?style=social)](https://twitter.com/intent/tweet?text=Wow:&url=https://github.com/fantastic91/mailhandler)

[Mailhandler](http://drupal.org/project/mailhandler) is a [Drupal 8](https://www.drupal.org/8) module that allows you to post nodes by email. 

It was developed as a part of [Google Summer of Code 2016](https://summerofcode.withgoogle.com/projects/#4520809229975552), following the [road map](https://www.drupal.org/node/2731519) and motivated by [Mailhandler for Drupal 7](https://www.drupal.org/project/mailhandler).

The Drupal 8 version of the module is based on [Inmail](https://www.drupal.org/project/inmail) module and takes mail (usually from an IMAP mailbox) and imports it as whatever type of content you choose. Beside the configured content type, you can specify the content type directly in your email subject. It enables you to select different authentication methods and mail analyzers as well.

Mailhandler Comment is a submodule that allows you to post comments by email.

Hugely powerful and flexible, Mailhandler includes a demo module to help you get started.

## Installation

To use this module you will need to have:
 - Installed [Drupal 8 Core](https://www.drupal.org/project/drupal)
 - [Inmail](https://www.drupal.org/project/inmail)
 - [Mailhandler](https://www.drupal.org/project/mailhandler) (Make sure you choose 8.x version of the module)
 - Recommended: [GnuPG PHP extension](http://php.net/manual/en/gnupg.setup.php) (Support for PGP-signed emails)

Take a look at the quick demo video that explains Mailhandler workflow:
[![Mailhandler Demo](https://i.vimeocdn.com/video/582471712.webp?mw=640&mh=360)](https://vimeo.com/175383067 "Mailhandler Demo")

## How-To

- What is the needed email format for Mailhandler?

All emails parsed by Mailhandler need to have a subject that begins with `[node][{content_type}]` (for nodes) or `[comment][{#entity_ID}]` (for comments). The first parameters is an entity type ID while the second one is a content type or an entity ID. Both parameters needs to be valid.

## Contributing

You can help by reporting bugs, suggesting features, reviewing feature specifications or just by sharing your opinion. Use [Drupal.org issues](https://www.drupal.org/project/issues/mailhandler?version=8.x) for all that.

However, if you are more into Github contribution, you can submit a pull request too.
- [Fork it!](https://github.com/fantastic91/mailhandler)
- Create your feature branch: `git checkout -b my-new-feature`
- Commit your changes: `git commit -am 'Add some feature'`
- Push to the branch: `git push origin my-new-feature`
- Submit a pull request

## Credits

This project has been developed as a part of [Google Summer of Code 2016](https://summerofcode.withgoogle.com/projects/#4520809229975552) by [Miloš Bovan](https://www.drupal.org/u/mbovan) and mentored by [Miro Dietiker](https://www.drupal.org/u/miro_dietiker) and [Primož Hmeljak](https://www.drupal.org/u/Primsi).

## License

https://www.gnu.org/licenses/gpl-2.0.html
    </content>
</snippet>
