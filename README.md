# Svg Symbols Plugin

<!-- **This README.md file should be modified to describe the features, installation, configuration, and general usage of this plugin.** -->

The **Svg Symbols** Plugin is for [Grav CMS](http://github.com/getgrav/grav).

FIXME: Description of the benefits of inlining SVG and
referencing it with `use`. Problems, EasySVG solves.

FIXME: Describe handling of `xlink:href`.

## Installation

<!-- Installing the Svg Symbols plugin can be done in one of two ways. The GPM (Grav Package Manager) installation method enables you to quickly and easily install the plugin with a simple terminal command, while the manual method enables you to do so via a zip file. -->

<!-- ### GPM Installation (Preferred) -->

<!-- The simplest way to install this plugin is via the [Grav Package Manager (GPM)](http://learn.getgrav.org/advanced/grav-gpm) through your system's terminal (also called the command line).  From the root of your Grav install type: -->

<!--     bin/gpm install svg-symbols -->

<!-- This will install the Svg Symbols plugin into your `/user/plugins` directory within Grav. Its files can be found under `/your/site/grav/user/plugins/svg-symbols`. -->

<!-- ### Manual Installation -->

To install this plugin, just download the zip version of this repository and unzip it under `/your/site/grav/user/plugins`. Then, rename the folder to `svg-symbols`. You can find these files on [GitHub](https://github.com/oliver-scholz/grav-plugin-svg-symbols) or via [GetGrav.org](http://getgrav.org/downloads/plugins#extras).

You should now have all the plugin files under

    /your/site/grav/user/plugins/svg-symbols
	
> NOTE: This plugin is a modular component for Grav which requires [Grav](http://github.com/getgrav/grav) and the [Error](https://github.com/getgrav/grav-plugin-error) and [Problems](https://github.com/getgrav/grav-plugin-problems) to operate.

<!-- ### Admin Plugin -->

<!-- If you use the admin plugin, you can install directly through the admin plugin by browsing the `Plugins` tab and clicking on the `Add` button. -->

## Configuration

Before configuring this plugin, you should copy the `user/plugins/svg-symbols/svg-symbols.yaml` to `user/config/plugins/svg-symbols.yaml` and only edit that copy.

Here is the default configuration and an explanation of available options:

```yaml
enabled: true
container_attributes: 'width="0" height="0" style="position:absolute;"'
remove:
  - dimensions: true
  - script_elements: true
domains_allowed:
  - example.com
prefix: 'symbol'
write_with_php_dom: false
```

Note that if you use the admin plugin, a file with your
configuration, and named svg-symbols.yaml will be saved in
the `user/config/plugins/` folder once the configuration is
saved in the admin.

## Usage

### Basic Usage

[PHP Streams](https://learn.getgrav.org/content/image-linking#php-streams)

```Twig
{% do svg.add('theme://images/smiley.svg', 'mysmiley') %}

{{ svg.symbols() }}

{{ svg.use('mysmiley') }}

```

### What gets into the HTML?

Say, you have a cute smiley SVG:

`smiley.svg:`

```SVG
<svg xmlns="http://www.w3.org/2000/svg"
     xmlns:xlink="http://www.w3.org/1999/xlink"
     width="150" height="150" viewBox="0 0 15 15">
  <defs>
    <radialGradient id="rg"
		    cx=".7" cy=".3" r=".5"
		    fx=".5" fy=".5">
      <stop offset="20%" stop-color="yellow"/>
      <stop offset="100%" stop-color="#Fa0"/>
    </radialGradient>
  </defs>
  <circle id="cic" cx="7.5" cy="7.5" r="7" stroke-width="1"
	  fill="url(#rg)" stroke="black" />
  <circle cx="4.5" cy="5.5" r="1" fill="black"/>
  <circle cx="10.5" cy="5.5" r="1" fill="black"/>

  <path stroke-width="1" stroke="black" fill="none"
	d="M4 8.5 C5 12, 10 12, 11 8.5"/>
</svg>

```

Then, the `{{ svg.use() }}` clause in Twig will insert
(without indentation and comments):

```HTML
<svg width="0" height="0" style="position:absolute;">
  <defs>
    <radialGradient id="rg"
		    cx=".7" cy=".3" r=".5"
		    fx=".5" fy=".5">
      <stop offset="20%" stop-color="yellow"/>
      <stop offset="100%" stop-color="#Fa0"/>
    </radialGradient>
  </defs>
  <symbol id="symbol-mysmiley">
    <circle id="cic" cx="7.5" cy="7.5" r="7" stroke-width="1"
	    fill="url(#rg)" stroke="black" />
    <circle cx="4.5" cy="5.5" r="1" fill="black"/>
    <circle cx="10.5" cy="5.5" r="1" fill="black"/>

    <path stroke-width="1" stroke="black" fill="none"
	  d="M4 8.5 C5 12, 10 12, 11 8.5"/>
  </symbol>
  <!-- If you have added other SVG files with svg.add() they would come here.
       -->
</svg>

```

and `{{ svg.use('mysmiley') }}` will render (again without indentation) as:

```HTML
<svg viewBox="0 0 15 15">
  <use xlink:href="#symbol-mysmiley"/>
</svg>
```

You can invoke `svg.use()` as often as you want in a
document, it will always insert only the latter markup
containing `<use>` That's basically the whole point about
this practise.

The following is particularily noteworthy:

- The `<defs>` element has been moved out of the `<symbol>`
  element defining the original SVG file and is the first
  element of the containing SVG. This is necessary in order
  for e.g. gradients as fill patterns to work.
  
- The `viewBox` attribute is defined on the referencing
  `<svg>` clause (the one containing `<use>`, not on the
  `<symbol>`.


Each `id` attribute used in the markup is prefixed with
`symbol-`. This is to minimize the risk of name
clashes. Normally, you don't have to deal with this. But
should you ever want to reference, for instance in CSS,
those IDs outside of EasySVG, you have to be aware of
it. (Generally you shouldn't, but that's up to you ...)

### The functions in more detail

### Manipulating SVG

```Twig
{% do svg.removeAttribute('smiley', '//circle[1]', 'stroke') %}
```

This removes the `stroke` attribute from the first `circle`
element in the symbol. The first argument is, as before, the
id, the second is an XPath expression that should return
one or more elements from which the attribute given in the
third argument is to be removed.

The primary intended purpose of this is to remove attributes
like `fill`, `stroke`, `stroke-width` etc. that you want to
style in CSS. This way, your SVG can look good while you're
editing it in Inkscape, without hindering your ability to
apply CSS.

If the XPath expression returns an _attribute node__, you
can omitt the third argument. The above is equivalent to:

```Twig
{% do svg.removeAttribute('smiley', '//circle[1]/@stroke') %}
```

! Naturally, you can only manipulate the SVG _before_ you
! insert it into the document with `svg.symbols()`. Since
! EasySVG does its reformatting only then, not on `svg.add()`,
! your XPath expression has to match against the original
! SVG's document structure. For instance, the document root is
! matched by `/svg` not by `/symbol`.


```Twig
{% do svg.setAttribute('mysmiley', '//circle[1]', 'fill', 'red') %}

{% do svg.setAttribute('mysmiley', '//circle[1]', 'fill', false) %}
```

<!-- ## Credits -->

<!-- **Did you incorporate third-party code? Want to thank somebody?** -->

## To Do

- [ ] Fix a couple of minor issues.
- [ ] Write documentation.
- [ ] Build a demo site.

### Long term

It would be really cool to get live injection of SVG,
cloning the logic that live.js applies to CSS. This way, you
could edit your SVG in Inkscape or Illustrator and see the
results live in your browser.


