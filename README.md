# PHP RST plugin for DokuWiki
    ---- plugin ----
    description: Parses RestructuredText files and blocks.
    author     : Chris Green
    email      : chris@isbd.co.uk
    type       : syntax
    lastupdate : 2015-10-07
    compatible : “Detritus” and newer
    depends    : 
    conflicts  :
    similar    : markdownExtra 
    tags       : formatting, markup_language
    downloadurl: 
    ----

##Download and Installation

Download and install the plugin using the Plugin Manager using the following URL. Refer to [[:Plugins]] on how to install plugins manually.



##Usage

If the page name ends with ''.rst'' suffix, it gets automatically parsed as RST . To use that markup in other pages, the content must be embedded in a rst block. For example:

    <rst>
    Header 1
    ========

    Paragraph

    Header 2
    --------

    - A
    - simple
    - list

    1. And
    2. numbered
    3. list

    Quite intuitive? *emphasis*, **strong**, etc.
    </rst>
