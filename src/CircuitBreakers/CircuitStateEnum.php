<?php
declare(strict_types = 1);

namespace Interrupt\CircuitBreakers;

enum CircuitStateEnum {
  // fail state, service is currently unavailable
  case OPEN;
  // transition state, if the service fails in this state, the circuit goes to OPEN, otherwise it goes to CLOSED
  case HALF_OPEN;
  // healthy state, service is currently available
  case CLOSED;
}
