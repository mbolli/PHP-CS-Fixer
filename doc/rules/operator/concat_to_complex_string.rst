=================================
Rule ``concat_to_complex_string``
=================================

Converts concatenated strings to complex strings.

Examples
--------

Example #1
~~~~~~~~~~

.. code-block:: diff

   --- Original
   +++ New
   @@ -1,2 +1,2 @@
    <?php
   -$foo = 'bar ' . baz() . ' baz' . $baz;
   +$foo = "bar {${baz()}}) baz{$baz}";
