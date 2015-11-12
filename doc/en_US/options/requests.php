You can customize key to look under for getting the language requested in fetching objects. This can be customized for purposes like conflict prevention with other translation systems, or just personal taste.

When someone visits the site, the language to be served is determined by the following checks, each overriding the previous if matched:

<ol>
	<li>The visitor's language according to their browser.</li>
	<li>The language specified in the URL (based on the scheme specified by the Redirection Method)</li>
	<li>The GET/POST query argument if specified.</li>
	<li>(Optional) The language of the requested object if it differs from the on previously detected.</li>
</ol>

If the language cannot be determined by any of the above means, the language specified as the default will be used.
