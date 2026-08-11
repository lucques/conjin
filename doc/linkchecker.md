# Linkchecker

LinkChecker exits with status 1 when it encounters either invalid links or enabled warnings. Redirect warnings therefore fail automated LinkChecker runs even when the redirect destination responds successfully.

Localization language-choice links intentionally include the `localization-explicit-choice=1` query parameter. Their destination records the selected language in a cookie and responds with a temporary redirect to the same URL without the marker so that it does not remain in bookmarks or shared URLs. The LinkChecker image ignores only the `http-redirected` warning for URLs containing this marker. LinkChecker still follows the redirect and checks the final response, while unexpected redirects elsewhere remain enabled warnings and continue to fail the run.
