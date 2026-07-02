<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  xmlns:sm="http://www.sitemaps.org/schemas/sitemap/0.9"
  xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"
  xmlns:xhtml="http://www.w3.org/1999/xhtml"
  exclude-result-prefixes="sm image xhtml">

  <xsl:output method="html" encoding="UTF-8" indent="yes"/>

  <!-- ── Root: detect document type ──────────────────────────────────── -->

  <xsl:template match="/">
    <html lang="en">
      <head>
        <meta charset="UTF-8"/>
        <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
        <title>XML Sitemap</title>
        <style>
          *, *::before, *::after { box-sizing: border-box; }
          body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                 margin: 0; background: #f4f6fa; color: #2c3e50; font-size: 14px; line-height: 1.5; }
          a { color: #1a73e8; text-decoration: none; }
          a:hover { text-decoration: underline; }

          /* Header */
          .sg-header { background: linear-gradient(135deg, #1565c0 0%, #0d47a1 100%);
                       color: #fff; padding: 20px 40px; }
          .sg-header-inner { max-width: 1100px; margin: 0 auto;
                             display: flex; align-items: center; justify-content: space-between;
                             flex-wrap: wrap; gap: 8px; }
          .sg-header h1 { margin: 0; font-size: 19px; font-weight: 700; }
          .sg-header p  { margin: 3px 0 0; font-size: 12px; opacity: 0.78; }
          .sg-header a  { color: rgba(255,255,255,0.88); }
          .sg-header a:hover { color: #fff; text-decoration: underline; }

          /* Container */
          .sg-wrap { max-width: 1100px; margin: 0 auto; padding: 24px 40px 48px; }

          /* Stats row */
          .sg-stats { display: flex; gap: 14px; margin-bottom: 20px; flex-wrap: wrap; }
          .sg-stat  { background: #fff; border-radius: 10px; padding: 14px 22px;
                      box-shadow: 0 1px 4px rgba(0,0,0,0.08); min-width: 120px; text-align: center; flex: 1; }
          .sg-stat .v { font-size: 28px; font-weight: 700; color: #1565c0; line-height: 1.1; }
          .sg-stat .l { font-size: 11px; color: #888; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 3px; }

          /* Table panel */
          .sg-panel { background: #fff; border-radius: 10px;
                      box-shadow: 0 1px 4px rgba(0,0,0,0.08); overflow: hidden; }
          table { width: 100%; border-collapse: collapse; }
          th { background: #f8f9fa; color: #555; font-size: 11px; font-weight: 600;
               text-transform: uppercase; letter-spacing: 0.6px; padding: 10px 16px;
               text-align: left; border-bottom: 2px solid #e9edf2; white-space: nowrap; }
          td { padding: 8px 16px; border-bottom: 1px solid #f0f2f5; vertical-align: middle; }
          tr:last-child td { border-bottom: none; }
          tr:hover td { background: #f8f9fa; }

          /* Columns */
          .col-n  { color: #bbb; font-size: 12px; text-align: right; width: 48px; padding-right: 12px; }
          .col-url { word-break: break-all; }
          .col-url a { color: #1565c0; }
          .col-dt { color: #888; font-size: 12px; white-space: nowrap; }
          .col-cf { color: #666; font-size: 12px; white-space: nowrap; }
          .col-img { white-space: nowrap; }

          /* Images */
          .sg-images { display: flex; flex-wrap: wrap; gap: 3px; margin-top: 5px; }
          .sg-img-thumb { display: block; width: 36px; height: 36px; object-fit: cover;
                          border-radius: 3px; border: 1px solid #dde1e8; flex-shrink: 0; }
          .img-count { display: inline-flex; align-items: center; justify-content: center;
                       min-width: 24px; padding: 2px 5px; border-radius: 4px; font-size: 11px;
                       font-weight: 700; background: #e3f2fd; color: #1565c0; }

          /* Hreflang alternates */
          .sg-alts { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 5px; }
          .sg-alt { display: inline-block; padding: 1px 6px; border-radius: 3px; font-size: 11px;
                    font-weight: 600; border: 1px solid #c5d4ea; background: #eef3fb; color: #1565c0;
                    text-decoration: none; }
          .sg-alt:hover { background: #dce8f7; }
          .sg-alt-xdef { background: #e8f5e9; border-color: #a5d6a7; color: #2e7d32; }

          /* Priority badges */
          .badge { display: inline-flex; align-items: center; justify-content: center;
                   min-width: 36px; padding: 2px 6px; border-radius: 5px;
                   font-size: 11px; font-weight: 700; }
          .p10 { background: #e8f5e9; color: #1b5e20; }
          .p08 { background: #f1f8e9; color: #33691e; }
          .p07 { background: #fffde7; color: #f57f17; }
          .p06 { background: #fff8e1; color: #e65100; }
          .p05 { background: #fce4ec; color: #880e4f; }
          .p03 { background: #f3e5f5; color: #4a148c; }
          .pxx { background: #f5f5f5; color: #9e9e9e; }

          /* Responsive */
          @media (max-width: 720px) {
            .sg-header { padding: 14px 16px; }
            .sg-wrap   { padding: 14px 16px 32px; }
            .hide-sm   { display: none; }
          }
        </style>
      </head>
      <body>
        <div class="sg-header">
          <div class="sg-header-inner">
            <div>
              <h1>XML Sitemap</h1>
              <p>Generated by <a href="https://oc-kit.com" target="_blank">oc-kit.com</a> Sitemap Generator</p>
            </div>
          </div>
        </div>
        <div class="sg-wrap">
          <xsl:choose>
            <xsl:when test="sm:sitemapindex">
              <xsl:apply-templates select="sm:sitemapindex"/>
            </xsl:when>
            <xsl:otherwise>
              <xsl:apply-templates select="sm:urlset"/>
            </xsl:otherwise>
          </xsl:choose>
        </div>
      </body>
    </html>
  </xsl:template>

  <!-- ── Sitemap Index ────────────────────────────────────────────────── -->

  <xsl:template match="sm:sitemapindex">
    <div class="sg-stats">
      <div class="sg-stat">
        <div class="v"><xsl:value-of select="count(sm:sitemap)"/></div>
        <div class="l">Sitemap files</div>
      </div>
    </div>
    <div class="sg-panel">
      <table>
        <thead>
          <tr>
            <th class="col-n">#</th>
            <th>Sitemap URL</th>
            <th class="hide-sm">Last Modified</th>
          </tr>
        </thead>
        <tbody>
          <xsl:for-each select="sm:sitemap">
            <tr>
              <td class="col-n"><xsl:value-of select="position()"/></td>
              <td class="col-url"><a href="{sm:loc}"><xsl:value-of select="sm:loc"/></a></td>
              <td class="col-dt hide-sm"><xsl:value-of select="sm:lastmod"/></td>
            </tr>
          </xsl:for-each>
        </tbody>
      </table>
    </div>
  </xsl:template>

  <!-- ── URL Set ──────────────────────────────────────────────────────── -->

  <xsl:template match="sm:urlset">
    <div class="sg-stats">
      <div class="sg-stat">
        <div class="v"><xsl:value-of select="count(sm:url)"/></div>
        <div class="l">URLs indexed</div>
      </div>
      <xsl:if test="sm:url/image:image">
        <div class="sg-stat">
          <div class="v"><xsl:value-of select="count(sm:url/image:image)"/></div>
          <div class="l">Images</div>
        </div>
      </xsl:if>
      <xsl:if test="sm:url/xhtml:link">
        <div class="sg-stat">
          <div class="v"><xsl:value-of select="count(sm:url[xhtml:link])"/></div>
          <div class="l">Hreflang</div>
        </div>
      </xsl:if>
    </div>
    <div class="sg-panel">
      <table>
        <thead>
          <tr>
            <th class="col-n">#</th>
            <th>URL</th>
            <xsl:if test="sm:url/image:image">
              <th class="col-img hide-sm">Img</th>
            </xsl:if>
            <th class="hide-sm">Last Modified</th>
            <th class="hide-sm">Frequency</th>
            <th>Priority</th>
          </tr>
        </thead>
        <tbody>
          <xsl:for-each select="sm:url">
            <xsl:variable name="p" select="sm:priority"/>
            <xsl:variable name="imgCount" select="count(image:image)"/>
            <tr>
              <td class="col-n"><xsl:value-of select="position()"/></td>
              <td class="col-url">
                <a href="{sm:loc}"><xsl:value-of select="sm:loc"/></a>
                <xsl:if test="image:image">
                  <div class="sg-images">
                    <xsl:for-each select="image:image">
                      <a href="{image:loc}" target="_blank" title="{image:loc}">
                        <img class="sg-img-thumb" src="{image:loc}" alt="" loading="lazy"/>
                      </a>
                    </xsl:for-each>
                  </div>
                </xsl:if>
                <xsl:if test="xhtml:link[@rel='alternate']">
                  <div class="sg-alts">
                    <xsl:for-each select="xhtml:link[@rel='alternate']">
                      <xsl:variable name="cls">
                        <xsl:choose>
                          <xsl:when test="@hreflang='x-default'">sg-alt sg-alt-xdef</xsl:when>
                          <xsl:otherwise>sg-alt</xsl:otherwise>
                        </xsl:choose>
                      </xsl:variable>
                      <a class="{$cls}" href="{@href}" target="_blank">
                        <xsl:choose>
                          <xsl:when test="@hreflang='x-default'">x-default</xsl:when>
                          <xsl:when test="contains(@hreflang, '-')">
                            <xsl:value-of select="substring-before(@hreflang, '-')"/>
                          </xsl:when>
                          <xsl:otherwise><xsl:value-of select="@hreflang"/></xsl:otherwise>
                        </xsl:choose>
                      </a>
                    </xsl:for-each>
                  </div>
                </xsl:if>
              </td>
              <xsl:if test="../sm:url/image:image">
                <td class="col-img hide-sm">
                  <xsl:if test="$imgCount > 0">
                    <span class="img-count"><xsl:value-of select="$imgCount"/></span>
                  </xsl:if>
                </td>
              </xsl:if>
              <td class="col-dt hide-sm"><xsl:value-of select="sm:lastmod"/></td>
              <td class="col-cf hide-sm"><xsl:value-of select="sm:changefreq"/></td>
              <td>
                <span>
                  <xsl:attribute name="class">badge <xsl:choose>
                    <xsl:when test="$p = '1.0'">p10</xsl:when>
                    <xsl:when test="$p = '0.9'">p08</xsl:when>
                    <xsl:when test="$p = '0.8'">p08</xsl:when>
                    <xsl:when test="$p = '0.7'">p07</xsl:when>
                    <xsl:when test="$p = '0.6'">p06</xsl:when>
                    <xsl:when test="$p = '0.5'">p05</xsl:when>
                    <xsl:when test="$p = '0.4'">p05</xsl:when>
                    <xsl:when test="$p = '0.3'">p03</xsl:when>
                    <xsl:otherwise>pxx</xsl:otherwise>
                  </xsl:choose></xsl:attribute>
                  <xsl:value-of select="$p"/>
                </span>
              </td>
            </tr>
          </xsl:for-each>
        </tbody>
      </table>
    </div>
  </xsl:template>

</xsl:stylesheet>