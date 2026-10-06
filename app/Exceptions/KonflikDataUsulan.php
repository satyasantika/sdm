<?php

namespace App\Exceptions;

use DomainException;

/** Data target berubah setelah usulan diajukan (BR-06). */
class KonflikDataUsulan extends DomainException {}
