<?php


// Based on CSS minifier. by  Matthias Mullie - see  https://github.com/matthiasmullie/minify/issues
 
 /**
 * CSS minifier.
 *
 * Please report bugs on https://github.com/matthiasmullie/minify/issues
 *
 * @author Matthias Mullie <minify@mullie.eu>
 * @author Tijs Verkoyen <minify@verkoyen.eu>
 *
 * @copyright Copyright (c) 2012, Matthias Mullie. All rights reserved.
 * @license MIT License
 */
 
 
 
class HTML_CSS_Minify
{
    /**
     * The data to be minified
     *
     * @var string[]
     */
    protected $data = array();
    /**
     * The base URL of the original file..
     *
     * @var string[]
     */
    var $baseURL = '';
    
    /**
     * Init the minify class - optionally, code may be passed along already.
     *
     *@param string $baseURL the url location normally served from . eg. /template/images/css
     *@param string $baseDir the location on the disk eg. /var/www/myproject/templates/images/css
     *@param array $files the list of files
     * 
     */
    
    public function __construct($baseURL, $baseDir, $files  )
    {
        $this->baseURL = $baseURL;
        foreach($files as $f) {
            $this->data[$baseURL . '/'. $f] = file_get_contents($baseDir. '/'. $f);
        }
        
    }

    
   
    // not enabled at present..
   
 
    /**
     * Combine CSS from import statements.
     * @import's will be loaded and their content merged into the original file,
     * to save HTTP requests.
     *
     * @param  string $source  The file to combine imports for.
     * @param  string $content The CSS content to combine imports for.
     * @return string
     */
    protected function combineImports($source, $content)
    {
        $importRegexes = array(
            // @import url(xxx)
            '/
            # import statement
            @import

            # whitespace
            \s+

                # open url()
                url\(

                    # (optional) open path enclosure
                    (?P<quotes>["\']?)

                        # fetch path
                        (?P<path>

                            # do not fetch data uris or external sources
                            (?!(
                                ["\']?
                                (data|https?):
                            ))

                            .+?
                        )

                    # (optional) close path enclosure
                    (?P=quotes)

                # close url()
                \)

                # (optional) trailing whitespace
                \s*

                # (optional) media statement(s)
                (?P<media>[^;]*)

                # (optional) trailing whitespace
                \s*

            # (optional) closing semi-colon
            ;?

            /ix',

            // @import 'xxx'
            '/

            # import statement
            @import

            # whitespace
            \s+

                # open path enclosure
                (?P<quotes>["\'])

                    # fetch path
                    (?P<path>

                        # do not fetch data uris or external sources
                        (?!(
                            ["\']?
                            (data|https?):
                        ))

                        .+?
                    )

                # close path enclosure
                (?P=quotes)

                # (optional) trailing whitespace
                \s*

                # (optional) media statement(s)
                (?P<media>[^;]*)

                # (optional) trailing whitespace
                \s*

            # (optional) closing semi-colon
            ;?

            /ix',
        );

        // find all relative imports in css
        $matches = array();
        foreach ($importRegexes as $importRegex) {
            if (preg_match_all($importRegex, $content, $regexMatches, PREG_SET_ORDER)) {
                $matches = array_merge($matches, $regexMatches);
            }
        }

        $search = array();
        $replace = array();

        // loop the matches
        foreach ($matches as $match) {
            // get the path for the file that will be imported
            $importPath = dirname($source).'/'.$match['path'];

            // only replace the import with the content if we can grab the
            // content of the file
            if (@file_exists($importPath) && is_file($importPath)) {
                // grab referenced file & minify it (which may include importing
                // yet other @import statements recursively)
                $minifier = new static($importPath);
                $importContent = $minifier->execute($source);

                // check if this is only valid for certain media
                if ($match['media']) {
                    $importContent = '@media '.$match['media'].'{'.$importContent.'}';
                }

                // add to replacement array
                $search[] = $match[0];
                $replace[] = $importContent;
            }
        }

        // replace the import statements
        $content = str_replace($search, $replace, $content);

        return $content;
    }

     
    /**
     * Minify the data.
     * Perform CSS optimizations.
     *
     * @param  string[optional] $serve_url Path where the new file will be served from 
     * @return string           The minified data.
     */
    public function minify($serve_url = null)
    {
        $content = '';

        // loop files
        foreach ($this->data as $source => $css) {
            /*
             * Let's first take out strings & comments, since we can't just remove
             * whitespace anywhere. If whitespace occurs inside a string, we should
             * leave it alone. E.g.:
             * p { content: "a   test" }
             */
            $this->extractStrings();
            $this->stripComments();
            $css = $this->replace($css);

            $css = $this->stripWhitespace($css);
            $css = $this->shortenHex($css);
            $css = $this->shortenZeroes($css);

            // restore the string we've extracted earlier
            $css = $this->restoreExtractedData($css);

            /*
             * If we'll save to a new path, we'll have to fix the relative paths
             * to be relative no longer to the source file, but to the new path.
             * If we don't write to a file, fall back to same path so no
             * conversion happens (because we still want it to go through most
             * of the move code...)
             */
            
            $source = empty($source) ? '' : $source;
            
            $css = $this->move(empty($serve_url) ?  $source : $serve_url, $css);

            // if no target path is given, relative paths were not converted, so
            // they'll still be relative to the source file then
            
            
            //$css = $this->importFiles($path ?: $source, $css);
            
            
            // combined imports are not done
            //$css = $this->combineImports($path ?: $source, $css);

            // combine css
            $content .= $css;
        }

        return $content;
    }

    /**
     * Moving a css file should update all relative urls.
     * Relative references (e.g. ../images/image.gif) in a certain css file,
     * will have to be updated when a file is being saved at another location
     * (e.g. ../../images/image.gif, if the new CSS file is 1 folder deeper)
     *
     * @param  Converter $converter Relative path converter
     * @param  string    $content   The CSS content to update relative urls for.
     * @return string
     */
    protected function move($serve_url, $content)
    {
        /*
         * Relative path references will usually be enclosed by url(). @import
         * is an exception, where url() is not necessary around the path (but is
         * allowed).
         * This *could* be 1 regular expression, where both regular expressions
         * in this array are on different sides of a |. But we're using named
         * patterns in both regexes, the same name on both regexes. This is only
         * possible with a (?J) modifier, but that only works after a fairly
         * recent PCRE version. That's why I'm doing 2 separate regular
         * expressions & combining the matches after executing of both.
         */
        $relativeRegexes = array(
            // url(xxx)
            '/
            # open url()
            url\(

                \s*

                # open path enclosure
                (?P<quotes>["\'])?

                    # fetch path
                    (?P<path>

                        # do not fetch data uris or external sources
                        (?!(
                            \s?
                            ["\']?
                            (data|https?):
                        ))

                        .+?
                    )

                # close path enclosure
                (?(quotes)(?P=quotes))

                \s*

            # close url()
            \)

            /ix',

            // @import "xxx"
            '/
            # import statement
            @import

            # whitespace
            \s+

                # we don\'t have to check for @import url(), because the
                # condition above will already catch these

                # open path enclosure
                (?P<quotes>["\'])

                    # fetch path
                    (?P<path>

                        # do not fetch data uris or external sources
                        (?!(
                            ["\']?
                            (data|https?):
                        ))

                        .+?
                    )

                # close path enclosure
                (?P=quotes)

            /ix',
        );

        // find all relative urls in css
        $matches = array();
        foreach ($relativeRegexes as $relativeRegex) {
            if (preg_match_all($relativeRegex, $content, $regexMatches, PREG_SET_ORDER)) {
                $matches = array_merge($matches, $regexMatches);
            }
        }

        $search = array();
        $replace = array();

        // loop all urls
        foreach ($matches as $match) {
            // determine if it's a url() or an @import match
            $type = (strpos($match[0], '@import') === 0 ? 'import' : 'url');
            
            
             
            
            // fix relative url
            $url = $this->convertPath($base_url_to, $match['path']);

            // build replacement
            $search[] = $match[0];
            if ($type == 'url') {
                $replace[] = 'url('.$url.')';
            } elseif ($type == 'import') {
                $replace[] = '@import "'.$url.'"';
            }
        }

        // replace urls
        $content = str_replace($search, $replace, $content);

        return $content;
    }

    /**
     * Shorthand hex color codes.
     * #FF0000 -> #F00
     *
     * @param  string $content The CSS content to shorten the hex color codes for.
     * @return string
     */
    protected function shortenHex($content)
    {
        $content = preg_replace('/(?<![\'"])#([0-9a-z])\\1([0-9a-z])\\2([0-9a-z])\\3(?![\'"])/i', '#$1$2$3', $content);

        return $content;
    }

    /**
     * Shorthand 0 values to plain 0, instead of e.g. -0em.
     *
     * @param  string $content The CSS content to shorten the zero values for.
     * @return string
     */
    protected function shortenZeroes($content)
    {
        // reusable bits of code throughout these regexes:
        // before & after are used to make sure we don't match lose unintended
        // 0-like values (e.g. in #000, or in http://url/1.0)
        // units can be stripped from 0 values, or used to recognize non 0
        // values (where wa may be able to strip a .0 suffix)
        $before = '(?<=[:(, ])';
        $after = '(?=[ ,);}])';
        $units = '(em|ex|%|px|cm|mm|in|pt|pc|ch|rem|vh|vw|vmin|vmax|vm)';

        // strip units after zeroes (0px -> 0)
        // NOTE: it should be safe to remove all units for a 0 value, but in
        // practice, Webkit (especially Safari) seems to stumble over at least
        // 0%, potentially other units as well. Only stripping 'px' for now.
        // @see https://github.com/matthiasmullie/minify/issues/60
        $content = preg_replace('/'.$before.'(-?0*(\.0+)?)(?<=0)px'.$after.'/', '\\1', $content);

        // strip 0-digits (.0 -> 0)
        $content = preg_replace('/'.$before.'\.0+'.$units.'?'.$after.'/', '0\\1', $content);
        // strip trailing 0: 50.10 -> 50.1, 50.10px -> 50.1px
        $content = preg_replace('/'.$before.'(-?[0-9]+\.[0-9]+)0+'.$units.'?'.$after.'/', '\\1\\2', $content);
        // strip trailing 0: 50.00 -> 50, 50.00px -> 50px
        $content = preg_replace('/'.$before.'(-?[0-9]+)\.0+'.$units.'?'.$after.'/', '\\1\\2', $content);
        // strip leading 0: 0.1 -> .1, 01.1 -> 1.1
        $content = preg_replace('/'.$before.'(-?)0+([0-9]*\.[0-9]+)'.$units.'?'.$after.'/', '\\1\\2\\3', $content);

        // strip negative zeroes (-0 -> 0) & truncate zeroes (00 -> 0)
        $content = preg_replace('/'.$before.'-?0+'.$units.'?'.$after.'/', '0\\1', $content);

        return $content;
    }

    /**
     * Strip comments from source code.
     */
    protected function stripComments()
    {
        $this->registerPattern('/\/\*.*?\*\//s', '');
    }

    /**
     * Strip whitespace.
     *
     * @param  string $content The CSS content to strip the whitespace for.
     * @return string
     */
    protected function stripWhitespace($content)
    {
        // remove leading & trailing whitespace
        $content = preg_replace('/^\s*/m', '', $content);
        $content = preg_replace('/\s*$/m', '', $content);

        // replace newlines with a single space
        $content = preg_replace('/\s+/', ' ', $content);

        // remove whitespace around meta characters
        // inspired by stackoverflow.com/questions/15195750/minify-compress-css-with-regex
        $content = preg_replace('/\s*([\*$~^|]?+=|[{};,>~]|!important\b)\s*/', '$1', $content);
        $content = preg_replace('/([\[(:])\s+/', '$1', $content);
        $content = preg_replace('/\s+([\]\)])/', '$1', $content);
        $content = preg_replace('/\s+(:)(?![^\}]*\{)/', '$1', $content);

        // whitespace around + and - can only be stripped in selectors, like
        // :nth-child(3+2n), not in things like calc(3px + 2px) or shorthands
        // like 3px -2px
        $content = preg_replace('/\s*([+-])\s*(?=[^}]*{)/', '$1', $content);

        // remove semicolon/whitespace followed by closing bracket
        $content = str_replace(';}', '}', $content);

        return trim($content);
    }
      /**
     * Strings are a pattern we need to match, in order to ignore potential
     * code-like content inside them, but we just want all of the string
     * content to remain untouched.
     *
     * This method will replace all string content with simple STRING#
     * placeholder text, so we've rid all strings from characters that may be
     * misinterpreted. Original string content will be saved in $this->extracted
     * and after doing all other minifying, we can restore the original content
     * via restoreStrings()
     *
     * @param string[optional] $chars
     */
    protected function extractStrings($chars = '\'"')
    {
        // PHP only supports $this inside anonymous functions since 5.4
        $minifier = $this;
        $callback = function ($match) use ($minifier) {
            if (!$match[1]) {
                /*
                 * Empty strings need no placeholder; they can't be confused for
                 * anything else anyway.
                 * But we still needed to match them, for the extraction routine
                 * to skip over this particular string.
                 */
                return $match[0];
            }
            $count = count($minifier->extracted);
            $placeholder = $match[1].$count.$match[1];
            $minifier->extracted[$placeholder] = $match[1].$match[2].$match[1];
            return $placeholder;
        };
        /*
         * The \\ messiness explained:
         * * Don't count ' or " as end-of-string if it's escaped (has backslash
         * in front of it)
         * * Unless... that backslash itself is escaped (another leading slash),
         * in which case it's no longer escaping the ' or "
         * * So there can be either no backslash, or an even number
         * * multiply all of that times 4, to account for the escaping that has
         * to be done to pass the backslash into the PHP string without it being
         * considered as escape-char (times 2) and to get it in the regex,
         * escaped (times 2)
         */
        $this->registerPattern('/(['.$chars.'])(.*?(?<!\\\\)(\\\\\\\\)*+)\\1/s', $callback);
    }

    
    function convertPath($in_to, $path)
    {
        require_once 'Net/URL.php';
        $a = new NetURL();
        $path = $a->resolvePath($this->baseURL . '/'. $path); // not sure if that's a good idea..
        $to = $a->resolvePath($in_to);
        
        
        
        $path1 = $path ? explode('/', $path) : array();
        $path2 = $to ? explode('/', $to) : array();
        $shared = array();
        // compare paths & strip identical ancestors
        foreach ($path1 as $i => $chunk) {
            if (isset($path2[$i]) && $path1[$i] == $path2[$i]) {
                $shared[] = $chunk;
            } else {
                break;
            }
        }
        $shared =  implode('/', $shared);
        
        
        $path = mb_substr($path, mb_strlen($shared));
        $to = mb_substr($to, mb_strlen($shared));
        
        $to = str_repeat('../', mb_substr_count($to, '/'));
        return $to . ltrim($path, '/');
    }
    
    
}