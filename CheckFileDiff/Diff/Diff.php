<?php

function diff() {
    exec("FC "."curr.txt "."old.txt >"."diff.txt");
}
