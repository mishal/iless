<?php

// a list of known and tolerated differences
return [
     112 => "  content: /* Sorry, unable to do javascript evaluation in PHP... With men it is impossible, but not with God: for with God all things are possible. Mark 10:27 */ is not equal to 8;", // less.js:   content: 8 is less than 9 too;
     113 => "  content: 8 is not equal to /* Sorry, unable to do javascript evaluation in PHP... With men it is impossible, but not with God: for with God all things are possible. Mark 10:27 */ too;", // less.js:   content: 9 is greater than 8;
];
