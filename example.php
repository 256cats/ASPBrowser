include_once __DIR__.'/simple_html_dom.php';
include_once __DIR__.'/ASPBrowser.php';
                                       

/**Remove spaces from string
 * @param $s
 * @return string
 */
function removeSpaces($s) {
    return trim(preg_replace('!\s+!', ' ', $s));
}

/**Print table of html results
 * @param simple_html_dom $dom
 */
function printTableData(simple_html_dom $dom) {
    foreach($dom->find('tr.gridViewRow, tr.gridViewAlternateRow') as $tr) {
        $td = $tr->find('td');
        echo removeSpaces($td[0]->innertext.';'.$td[1]->innertext.';'.$td[2]->innertext.';');
        echo $td[3]->find('a', 0)->href.';';
        echo $td[4]->find('img', 0)->alt."\n";
    }
}

$url = 'https://ucpi.sco.ca.gov/ucp/Default.aspx';
$browser = new ASPBrowser();
$browser->exclude = array('ctl00$ContentPlaceHolder1$btnClear');
$browser->doGetRequest($url); // get form
$resultPage = $browser->doPostRequest($url, array('ctl00$ContentPlaceHolder1$txtLastName' => 'smith')); // hit seach, get 1st page of results
$browser->exclude = array('ctl00$ContentPlaceHolder1$btnClearInd', 'ctl00$ContentPlaceHolder1$ddlPageSize', 'ctl00$ContentPlaceHolder1$btnSearchInd'); // set exclude for search results
for($i = 2; $i < 5; $i++) {
    printTableData($resultPage);
    $resultPage = $browser->doPostBack($browser->lastUrl, 'ctl00$ContentPlaceHolder1$gvResults', 'Page$'.$i);
}
printTableData($resultPage);
$resultPage->clear();

