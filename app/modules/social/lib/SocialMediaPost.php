<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

abstract class SocialMediaPost extends KurogoDataObject
{
    protected $id;
    protected $author;
    protected $url;
    protected $body;
    protected $created;
    protected $parent_id;
    protected $thread_id;
    protected $likes=0;
    protected $replyCount=0;
    protected $replies=array();
    protected $links=array();
    protected $images=array();
    protected $retriever;
    protected $serviceName;

    abstract public function getReplyURL();
    abstract public function getLikeURL();

    public function filterItem($filters) {
        foreach ($filters as $filter=>$value) {
            switch ($filter)
            {
                case 'search': //case insensitive
                    return  (stripos($this->getBody(), $value)!==FALSE);
                    break;
            }
        }   
        
        return true;     
    }

    public function getServiceName()
    {
        return $this->serviceName;
    }
    
    public function getAuthor() {
        return $this->author;
    }

    public function setAuthor($author) {
        $this->author = $author;
    }

    public function getURL() {
        return $this->url;
    }

    public function setURL($url) {
        $this->url = $url;
    }
    
    public function getBody() {
        return $this->body;
    }

    public function setBody($body) {
        $this->body = $body;
    }

    public function getCreated() {
        return $this->created;
    }

    public function setCreated(DateTime $created) {
        $this->created = $created;
    }

    public function getParentID() {
        return $this->parent_id;
    }

    public function setParentID($parent_id) {
        $this->parent_id= $parent_id;
    }

    public function getThreadID() {
        return $this->thread_id;
    }

    public function setThreadID($thread_id) {
        $this->thread_id= $thread_id;
    }
    
    public function getLikeCount() {
        return $this->likes;
    }

    public function setLikeCount($likes) {
        $this->likes = $likes;
    }

    public function getReplyCount() {
        return $this->replyCount;
    }

    public function setReplyCount($replyCount) {
        $this->replyCount = $replyCount;
    }

    public function getReplies() {
        return $this->replies;
    }

    public function addReply(SocialMediaPost $reply) {
        $this->replies[] = $reply;
        $this->setReplyCount(count($this->replies));
    }

    public function addLink($link) {
        $this->links[] = $link;
    }

    public function getLinks() {
        return $this->links;
    }

    public function addImage($image) {
        $this->images[] = $image;
    }

    public function getImages() {
        return $this->images;
    }

    /* File:        linkify.php
     * Repository:  https://github.com/jmrware/LinkifyURL
     * Version:     20101010_1000
     * Copyright:   (c) 2010 Jeff Roberson - http://jmrware.com
     * MIT License: http://www.opensource.org/licenses/mit-license.php
     *
     * Summary: This script linkifys http URLs on a page.
     *
     * Usage:   See example page: linkify.html
     */
    public function linkify($text) {
        $url_pattern = '/# Rev:20100913_0900 github.com\/jmrware\/LinkifyURL
        # Match http & ftp URL that is not already linkified.
          # Alternative 1: URL delimited by (parentheses).
          (\()                     # $1  "(" start delimiter.
          ((?:ht|f)tps?:\/\/[a-z0-9\-._~!$&\'()*+,;=:\/?#[\]@%]+)  # $2: URL.
          (\))                     # $3: ")" end delimiter.
        | # Alternative 2: URL delimited by [square brackets].
          (\[)                     # $4: "[" start delimiter.
          ((?:ht|f)tps?:\/\/[a-z0-9\-._~!$&\'()*+,;=:\/?#[\]@%]+)  # $5: URL.
          (\])                     # $6: "]" end delimiter.
        | # Alternative 3: URL delimited by {curly braces}.
          (\{)                     # $7: "{" start delimiter.
          ((?:ht|f)tps?:\/\/[a-z0-9\-._~!$&\'()*+,;=:\/?#[\]@%]+)  # $8: URL.
          (\})                     # $9: "}" end delimiter.
        | # Alternative 4: URL delimited by <angle brackets>.
          (<|&(?:lt|\#60|\#x3c);)  # $10: "<" start delimiter (or HTML entity).
          ((?:ht|f)tps?:\/\/[a-z0-9\-._~!$&\'()*+,;=:\/?#[\]@%]+)  # $11: URL.
          (>|&(?:gt|\#62|\#x3e);)  # $12: ">" end delimiter (or HTML entity).
        | # Alternative 5: URL not delimited by (), [], {} or <>.
          (                        # $13: Prefix proving URL not already linked.
            (?: ^                  # Can be a beginning of line or string, or
            | [^=\s\'"\]]          # a non-"=", non-quote, non-"]", followed by
            ) \s*[\'"]?            # optional whitespace and optional quote;
          | [^=\s]\s+              # or... a non-equals sign followed by whitespace.
          )                        # End $13. Non-prelinkified-proof prefix.
          ( \b                     # $14: Other non-delimited URL.
            (?:ht|f)tps?:\/\/      # Required literal http, https, ftp or ftps prefix.
            [a-z0-9\-._~!$\'()*+,;=:\/?#[\]@%]+ # All URI chars except "&" (normal*).
            (?:                    # Either on a "&" or at the end of URI.
              (?!                  # Allow a "&" char only if not start of an...
                &(?:gt|\#0*62|\#x0*3e);                  # HTML ">" entity, or
              | &(?:amp|apos|quot|\#0*3[49]|\#x0*2[27]); # a [&\'"] entity if
                [.!&\',:?;]?        # followed by optional punctuation then
                (?:[^a-z0-9\-._~!$&\'()*+,;=:\/?#[\]@%]|$)  # a non-URI char or EOS.
              ) &                  # If neg-assertion true, match "&" (special).
              [a-z0-9\-._~!$\'()*+,;=:\/?#[\]@%]* # More non-& URI chars (normal*).
            )*                     # Unroll-the-loop (special normal*)*.
            [a-z0-9\-_~$()*+=\/#[\]@%]  # Last char can\'t be [.!&\',;:?]
          )                        # End $14. Other non-delimited URL.
        /imx';
        $url_replace = '$1$4$7$10$13<a href="$2$5$8$11$14">$2$5$8$11$14</a>$3$6$9$12';
        return preg_replace($url_pattern, $url_replace, $text);
    }
}
