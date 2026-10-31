<?php

namespace App\Support;

use RuntimeException;

/**
 * A location search that could not be completed, so there are no matches to show.
 */
class LocationSearchFailed extends RuntimeException {}
