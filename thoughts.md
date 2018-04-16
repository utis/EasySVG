# Thoughts

## Current API

```Twig
{# Add an SVG file as an asset. #}
{% do add_svg_symbol('theme://images/scary-smiley.svg', 'scary-smiley') %}

{# Insert it all as symbols wrapped in <svg style="display: none"> as above. #}
{{ svg_symbols() }}

{# Use them in the document. #}
{{ use_svg_symbol('scary-smiley', 'svg-large') }}
```

## Features to be added

* Adding directories

* Using xpath expression to manipulate SVG

	* Remove attributes like `fill` or `stroke` etc.
	
	* Add IDs.
	
* For development: Add comments identifying the source. Then use javascript
  adapted from live.js to update SVGs in the browser dynamically.
  
* For development: Specify optimization folder and run svgo for file
  creation. (Careful!)
  
## Thoughts about API
  
First brain storming:

```Twig

{% Inline just here. This should print a message if the SVG is used more
than once. %}

{{ svg.add("theme://images/example.svg")->useOnce() }}


{% Add to "assets" and use %}

{{ svg.add("theme://images/example.svg") }}

{{ svg.symbols() }}

{{ svg.use('example') }}


{% Add (as an SVG symbol) to "assets" and insert the "<svg><use ..." clause
immediately. Probably not that a good idea ... Putting symbol definitions
below the use clause breaks with some browsers (?) and it might be
confusing to users. %}

{{ svg.add("theme://images/example.svg").use() }}

{% Add and remove fill. %}

{% do  svg.add("theme://images/example.svg").removeAttr("/rect", "fill") %}

{% Doing it in separate places. %}

{{ svg.addDir("theme://images/svg/*") }}

{% do svg.get('example').removeAttr("/rect", "fill") %}

```

Note to self: Trying to mimic Grav's image API is not a good idea. The
workings of images are entirely different. Stick to the asset manager
paradigma.



### Add single file.
``` Twig

{% do svg.add("theme://images/example.svg") %}
{# ==> ID is 'symbol-example' #}

{% do svg.add("theme://images/example.svg", false, "myId") %}
{# ==> ID is symbol-myId #}

{% do svg.add("theme://images/example.svg", false, "myId", "myPrefix") %}
{# ==> ID is myPrefix-myId #}

```

### Add directory

```Twig
{% do svg.addDir("theme://images/svg/*") %}
{# ==> IDs have the form `symbol-<file name sans suffix>`. #}

{% do svg.addDir("theme://images/svg/*", false, "myPrefix") %}
{# ==> IDs have the form `myPrefix-<file name sans suffix>`. #}
```

! Caveat

```Twig
{% do svg.add("theme://images/svg/example.svg") %}
{% do svg.add("theme://images/other-svg/example.svg") %}
```

Should probably make the second ID `symbol-other-svg-example` and warn
about this with:

```PHP
$grav['log']->warning('My warning message');

```

### 
