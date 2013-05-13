<!-- /*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */ -->


<!-- This XSLT transforms the Kaltura Generic XML feed into JSON. This file will need to be uploaded to Kaltura when creating a syndicated feed.
        When creating the feed, the 'Feed Type' should be 'Flexible Format Feed' and this file should be used as the XSL -->

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output method="text" omit-xml-declaration="yes" />
    <xsl:param name="KalturaHasNextItem" />
     
    <xsl:template name="rss" match="/">{<xsl:for-each select="rss">
        <xsl:variable name="indent"><xsl:text>          </xsl:text></xsl:variable>
        <xsl:variable name="channel-title"><xsl:call-template name="safe-json-string"><xsl:with-param name="text" select="string(channel/title)" /></xsl:call-template></xsl:variable>
        <xsl:variable name="channel-link"><xsl:call-template name="safe-json-string"><xsl:with-param name="text" select="string(channel/link)" /></xsl:call-template></xsl:variable>
        <xsl:variable name="channel-description"><xsl:call-template name="safe-json-string"><xsl:with-param name="text" select="string(channel/description)" /></xsl:call-template></xsl:variable>
         
        <xsl:attribute name="version">
          <xsl:value-of select="string(@version)"/>
        </xsl:attribute>
       "channel":{
            "title":"<xsl:value-of select="$channel-title"/>",
            "link":"<xsl:value-of select="$channel-link"/>",
            "description":"<xsl:value-of select="$channel-description"/>",
            "items": [<xsl:apply-templates name="item" select="channel/items/item"/>
            ]
        }
      </xsl:for-each>     
    }</xsl:template>
         
    <!-- Item Property-->
    <xsl:template name="item" match="item">
        {
        <xsl:apply-templates select="*[not(name() = 'content' or name() = 'subTitle')]"><xsl:with-param name="indent"><xsl:text>          </xsl:text></xsl:with-param></xsl:apply-templates>,
        "itemContent" : [ <xsl:apply-templates name="content" select="content" /> ],
        "subTitles" : [ <xsl:apply-templates name="subTitle" select="subTitle" /> ]
        }<xsl:if test="($KalturaHasNextItem = '1') or (count(/rss/channel/items/item) &gt; 1 and position() &lt; last())">,</xsl:if>
    </xsl:template>   

    <!-- Content Property - convert content nodes into json objects in "itemContent" array -->
    <xsl:template name="content" match="content" >
        {
            <xsl:apply-templates select="@*"><xsl:with-param name="indent"><xsl:text> </xsl:text></xsl:with-param></xsl:apply-templates>,
            <xsl:apply-templates select="*"><xsl:with-param name="indent"><xsl:text> </xsl:text></xsl:with-param></xsl:apply-templates>
        }<xsl:if test="(count(../content) &gt; 1 and position() &lt; last())">,</xsl:if>
    </xsl:template>

    <!-- SubTitle Property - convert into JSON Object inside 'subTitles' JSON Array -->
    <xsl:template name="subTitle" match="subTitle" >
        {
            <xsl:apply-templates select="@*"><xsl:with-param name="indent"><xsl:text> </xsl:text></xsl:with-param></xsl:apply-templates>,
            <xsl:apply-templates select="*"><xsl:with-param name="indent"><xsl:text> </xsl:text></xsl:with-param></xsl:apply-templates>
        }<xsl:if test="(count(../subTitle) &gt; 1 and position() &lt; last())">,</xsl:if>
    </xsl:template>
 
     
    <!-- Object or Element Property-->
    <xsl:template match="*">
        <xsl:param name="indent"/>
        <xsl:value-of select="$indent"/>
        <xsl:text>"</xsl:text>
        <xsl:value-of select="name()"/>" : <xsl:call-template name="Properties"><xsl:with-param name="indent" select="$indent"/></xsl:call-template>
    </xsl:template>
 
    <!-- Array Element -->
    <xsl:template match="*" mode="ArrayElement">
        <xsl:param name="indent"/>
        <xsl:value-of select="$indent"/>
        <xsl:call-template name="Properties"><xsl:with-param name="indent" select="$indent"/></xsl:call-template>
    </xsl:template>
 
    <!-- Object Properties -->
    <xsl:template name="Properties">
        <xsl:param name="indent"/>
        <xsl:variable name="childName" select="name(*[1])"/>
        <xsl:choose>
            <xsl:when test="not(*|@*)">
                <xsl:variable name="myVar1">
                    <xsl:text>"</xsl:text>
                    <xsl:call-template name="safe-json-string">
                        <xsl:with-param name="text" select="." />
                    </xsl:call-template>
                    <xsl:text>"</xsl:text>
                </xsl:variable>
                <xsl:value-of select="$myVar1" disable-output-escaping="yes"/>
            </xsl:when>
            <xsl:when test="count(*[name()=$childName]) > 1">
                <xsl:text>{
            </xsl:text>
                <xsl:value-of select="$indent"/>
                <xsl:text>"</xsl:text>
                <xsl:value-of select="$childName"/>
                <xsl:text>" : [
        </xsl:text>
                <xsl:apply-templates select="*" mode="ArrayElement">
                    <xsl:with-param name="indent"><xsl:value-of select="$indent"/><xsl:text>      </xsl:text></xsl:with-param>
                </xsl:apply-templates>
                <xsl:text>
            </xsl:text>
                <xsl:value-of select="$indent"/>
                <xsl:text>]
        </xsl:text>
                <xsl:value-of select="$indent"/>}
            </xsl:when>
            <xsl:otherwise>
                <xsl:text>{
        </xsl:text>
                <xsl:apply-templates select="@*">
                    <xsl:with-param name="indent"><xsl:value-of select="$indent"/><xsl:text>  </xsl:text></xsl:with-param>
                </xsl:apply-templates>
                <xsl:if test="(count(@*) > 0) and (count(*[name()=$childName]) > 0)"><xsl:text>,
        </xsl:text>
                </xsl:if>
                <xsl:apply-templates select="*"><xsl:with-param name="indent"><xsl:value-of select="$indent"/><xsl:text>    </xsl:text></xsl:with-param></xsl:apply-templates>
                <xsl:text>
        </xsl:text>
                <xsl:value-of select="$indent"/>
                <xsl:text>}</xsl:text>
            </xsl:otherwise>
        </xsl:choose>
        <xsl:if test="following-sibling::*">,
        </xsl:if>
    </xsl:template>
 
    <!-- Attribute Property -->
    <xsl:template match="@*">
        <xsl:param name="indent"/>
        <xsl:variable name="myVar">
            <xsl:call-template name="safe-json-string">
                <xsl:with-param name="text" select="." />
            </xsl:call-template>
        </xsl:variable>
        <xsl:value-of select="$indent"/>
        <xsl:text>"</xsl:text>
        <xsl:value-of select="name()"/>" : "<xsl:value-of select="$myVar"/>
        <xsl:text>"</xsl:text>      
        <xsl:if test="position() != last()">,
        </xsl:if>
    </xsl:template>
     
     
    <!-- replace string -->
    <xsl:template name="string-replace-all">
    <xsl:param name="text" />
    <xsl:param name="replace" />
    <xsl:param name="by" />
    <xsl:choose>
      <xsl:when test="contains($text, $replace)">
        <xsl:value-of select="substring-before($text,$replace)" />
        <xsl:value-of select="$by" />
        <xsl:call-template name="string-replace-all">
          <xsl:with-param name="text" select="substring-after($text,$replace)" />
          <xsl:with-param name="replace" select="$replace" />
          <xsl:with-param name="by" select="$by" />
        </xsl:call-template>
      </xsl:when>
      <xsl:otherwise>
        <xsl:value-of select="$text" />
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
 
    <xsl:template name="safe-json-string">
        <xsl:param name="text" />
        <xsl:variable name="myVar1Temp">
            <xsl:call-template name="string-replace-all">
                  <xsl:with-param name="text" select="$text" />
                  <xsl:with-param name="replace" select="'\'" />
                  <xsl:with-param name="by" select="'\\'" />
            </xsl:call-template>              
        </xsl:variable>
        <xsl:variable name="myVar1">
            <xsl:call-template name="string-replace-all">
                  <xsl:with-param name="text" select="$myVar1Temp" />
                  <xsl:with-param name="replace" select="'&quot;'" />
                  <xsl:with-param name="by" select="'\&quot;'" />
            </xsl:call-template>
        </xsl:variable>
        <xsl:value-of select="$myVar1"/>
    </xsl:template>
   
</xsl:stylesheet>