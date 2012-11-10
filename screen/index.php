<?php

require_once ('tools.php');

echo "<html>\n";
echo "<head>\n";
echo "<link rel=\"stylesheet\" href=\"/lib/css/fonts.css\" type=\"text/css\"/>\n";
echo "<style>\n";
echo ".font-loader {\n";
echo "  visibility: hidden;\n";
echo "  position: absolute;\n";
echo "}\n";
echo ".ubuntu {\n";
echo "  font-family: Ubuntu, Sans-serif;\n";
echo "  font-weight: normal;\n";
echo "}\n";
echo ".ubuntu-light {\n";
echo "  font-family: Ubuntu, Sans-serif;\n";
echo "  font-weight: lighter;\n";
echo "}\n";
echo "</style>\n";
echo "</head>\n";
echo "<body>\n";
// preload fonts
echo "<span class=\"font-loader\">\n";
echo "  <span class=\"ubuntu\"></span>\n";
echo "  <span class=\"ubuntu-light\"></span>\n";
echo "</span>\n";
// should use a local copy to avoid stupid proxy issues
echo "<script type=\"text/javascript\" src=\"http://code.jquery.com/jquery-1.8.2.js\"\></script>\n";
echo "<script type=\"text/javascript\" src=\"js/screen.js\"></script>\n";
echo "</body>\n";
echo "</html>";

?>
