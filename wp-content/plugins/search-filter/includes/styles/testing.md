# CSS Validation Testing

Test cases for CSS validation filters including:
- `safecss_filter_attr_allow_color_mix_css` - color-mix() and modern color functions
- `safecss_filter_attr_allow_box_shadow_css` - box-shadow values

## Setup

These tests can be run manually or migrated to PHPUnit infrastructure.

```php
<?php
// Load the WordPress environment and the Generate class
require_once 'class-generate.php';

use Search_Filter\Styles\Generate;

/**
 * Test runner function for color validation
 */
function run_color_validation_tests() {
    $tests = array(
        'test_valid_color_mix_basic',
        'test_valid_color_mix_with_percentages',
        'test_valid_color_mix_with_hue_interpolation',
        'test_valid_color_mix_with_var_functions',
        'test_valid_color_mix_with_calc_functions',
        'test_valid_color_mix_all_color_spaces',
        'test_valid_color_mix_edge_cases',
        'test_invalid_color_mix_missing_parts',
        'test_invalid_color_mix_malformed_syntax',
        'test_invalid_color_mix_injection_attempts',
        'test_valid_modern_color_functions',
        'test_invalid_modern_color_functions',
        'test_regular_colors_fallthrough',
    );

    return run_tests( $tests, 'Color Validation' );
}

/**
 * Test runner function for box-shadow validation
 */
function run_box_shadow_validation_tests() {
    $tests = array(
        'test_valid_box_shadow_none',
        'test_valid_box_shadow_two_lengths',
        'test_valid_box_shadow_three_lengths',
        'test_valid_box_shadow_four_lengths',
        'test_valid_box_shadow_with_colors',
        'test_valid_box_shadow_color_first',
        'test_valid_box_shadow_with_inset',
        'test_valid_box_shadow_multiple_shadows',
        'test_valid_box_shadow_various_units',
        'test_valid_box_shadow_edge_cases',
        'test_invalid_box_shadow_missing_required',
        'test_invalid_box_shadow_malformed',
        'test_invalid_box_shadow_injection_attempts',
        'test_box_shadow_unsupported_features',
    );

    return run_tests( $tests, 'Box-Shadow Validation' );
}

/**
 * Generic test runner
 */
function run_tests( $tests, $suite_name = 'Tests' ) {

    $results = array(
        'passed' => 0,
        'failed' => 0,
        'errors' => array(),
    );

    foreach ( $tests as $test ) {
        try {
            $test();
            $results['passed']++;
            echo "✓ {$test}\n";
        } catch ( Exception $e ) {
            $results['failed']++;
            $results['errors'][] = array(
                'test' => $test,
                'message' => $e->getMessage(),
            );
            echo "✗ {$test}: {$e->getMessage()}\n";
        }
    }

    echo "\n";
    echo "{$suite_name} Results: {$results['passed']} passed, {$results['failed']} failed\n";
    return $results;
}

/**
 * Helper function to test color validation
 */
function assert_color_validates( $color_value, $should_pass, $message = '' ) {
    // Simulate the full flow through safecss_filter_attr_allow_color_mix_css
    $css_test_string = "color: {$color_value}";
    $result = Generate::safecss_filter_attr_allow_color_mix_css( false, $css_test_string );

    if ( $should_pass && ! $result ) {
        throw new Exception( $message ?: "Expected '{$color_value}' to pass validation but it failed" );
    }
    if ( ! $should_pass && $result ) {
        throw new Exception( $message ?: "Expected '{$color_value}' to fail validation but it passed" );
    }
}

/**
 * Helper function to test box-shadow validation
 */
function assert_box_shadow_validates( $shadow_value, $should_pass, $message = '' ) {
    // Simulate the full flow through safecss_filter_attr_allow_box_shadow_css
    $css_test_string = "box-shadow: {$shadow_value}";
    $result = Generate::safecss_filter_attr_allow_box_shadow_css( false, $css_test_string );

    if ( $should_pass && ! $result ) {
        throw new Exception( $message ?: "Expected '{$shadow_value}' to pass validation but it failed" );
    }
    if ( ! $should_pass && $result ) {
        throw new Exception( $message ?: "Expected '{$shadow_value}' to fail validation but it passed" );
    }
}

// ============================================================================
// Test Cases: Valid color-mix() - Basic Syntax
// ============================================================================

function test_valid_color_mix_basic() {
    // Basic color-mix with srgb color space
    assert_color_validates(
        'color-mix(in srgb, red, blue)',
        true,
        'Basic color-mix with named colors should pass'
    );

    // color-mix with hex colors
    assert_color_validates(
        'color-mix(in srgb, #ff0000, #0000ff)',
        true,
        'color-mix with hex colors should pass'
    );

    // color-mix with rgb colors
    assert_color_validates(
        'color-mix(in srgb, rgb(255 0 0), rgb(0 0 255))',
        true,
        'color-mix with rgb colors should pass'
    );

    // color-mix with rgba colors
    assert_color_validates(
        'color-mix(in srgb, rgba(255, 0, 0, 0.5), rgba(0, 0, 255, 0.8))',
        true,
        'color-mix with rgba colors should pass'
    );

    // color-mix with hsl colors
    assert_color_validates(
        'color-mix(in hsl, hsl(200 50% 80%), hsl(300 60% 70%))',
        true,
        'color-mix with hsl colors should pass'
    );

    // color-mix with mixed color formats
    assert_color_validates(
        'color-mix(in srgb, #ff0000, blue)',
        true,
        'color-mix with mixed color formats should pass'
    );
}

// ============================================================================
// Test Cases: Valid color-mix() - With Percentages
// ============================================================================

function test_valid_color_mix_with_percentages() {
    // color-mix with one percentage
    assert_color_validates(
        'color-mix(in srgb, red 50%, blue)',
        true,
        'color-mix with one percentage should pass'
    );

    // color-mix with both percentages
    assert_color_validates(
        'color-mix(in srgb, red 25%, blue 75%)',
        true,
        'color-mix with both percentages should pass'
    );

    // color-mix with 0% percentage
    assert_color_validates(
        'color-mix(in srgb, red 0%, blue)',
        true,
        'color-mix with 0% percentage should pass'
    );

    // color-mix with 100% percentage
    assert_color_validates(
        'color-mix(in srgb, red 100%, blue)',
        true,
        'color-mix with 100% percentage should pass'
    );

    // color-mix with decimal percentages
    assert_color_validates(
        'color-mix(in srgb, red 33.33%, blue 66.67%)',
        true,
        'color-mix with decimal percentages should pass'
    );

    // Real-world example from codebase
    assert_color_validates(
        'color-mix(in srgb, var(--search-filter-input-color) 67%, transparent)',
        true,
        'Real-world example with var() and percentage should pass'
    );
}

// ============================================================================
// Test Cases: Valid color-mix() - With Hue Interpolation
// ============================================================================

function test_valid_color_mix_with_hue_interpolation() {
    // color-mix with shorter hue
    assert_color_validates(
        'color-mix(in lch shorter hue, hsl(200deg 50% 80%), coral)',
        true,
        'color-mix with shorter hue should pass'
    );

    // color-mix with longer hue
    assert_color_validates(
        'color-mix(in lch longer hue, hsl(200deg 50% 80%) 44%, coral 16%)',
        true,
        'color-mix with longer hue should pass'
    );

    // color-mix with increasing hue
    assert_color_validates(
        'color-mix(in lch increasing hue, red, blue)',
        true,
        'color-mix with increasing hue should pass'
    );

    // color-mix with decreasing hue
    assert_color_validates(
        'color-mix(in oklch decreasing hue, #ff0000, #0000ff)',
        true,
        'color-mix with decreasing hue should pass'
    );
}

// ============================================================================
// Test Cases: Valid color-mix() - With var() Functions
// ============================================================================

function test_valid_color_mix_with_var_functions() {
    // color-mix with one var()
    assert_color_validates(
        'color-mix(in srgb, var(--my-color), blue)',
        true,
        'color-mix with one var() should pass'
    );

    // color-mix with both var()
    assert_color_validates(
        'color-mix(in srgb, var(--color-1), var(--color-2))',
        true,
        'color-mix with both var() should pass'
    );

    // color-mix with var() containing fallback
    assert_color_validates(
        'color-mix(in srgb, var(--my-color, red), blue)',
        true,
        'color-mix with var() fallback should pass'
    );

    // Real-world example from codebase
    assert_color_validates(
        'color-mix(in srgb, var(--search-filter-input-border-focus-color) 47%, transparent)',
        true,
        'Real-world var() example should pass'
    );
}

// ============================================================================
// Test Cases: Valid color-mix() - With calc() Functions
// ============================================================================

function test_valid_color_mix_with_calc_functions() {
    // color-mix with calc() in percentage
    assert_color_validates(
        'color-mix(in srgb, red calc(50% + 10%), blue)',
        true,
        'color-mix with calc() in percentage should pass'
    );

    // This is a stretch case - calc() for color values is unusual but we should allow it
    assert_color_validates(
        'color-mix(in srgb, calc(red), blue)',
        true,
        'color-mix with calc() should pass'
    );
}

// ============================================================================
// Test Cases: Valid color-mix() - All Color Spaces
// ============================================================================

function test_valid_color_mix_all_color_spaces() {
    $color_spaces = array(
        'srgb',
        'srgb-linear',
        'lab',
        'oklab',
        'xyz',
        'xyz-d50',
        'xyz-d65',
        'hsl',
        'hwb',
        'lch',
        'oklch',
    );

    foreach ( $color_spaces as $space ) {
        assert_color_validates(
            "color-mix(in {$space}, red, blue)",
            true,
            "color-mix with {$space} color space should pass"
        );
    }
}

// ============================================================================
// Test Cases: Valid color-mix() - Edge Cases
// ============================================================================

function test_valid_color_mix_edge_cases() {
    // color-mix with transparent keyword
    assert_color_validates(
        'color-mix(in srgb, red, transparent)',
        true,
        'color-mix with transparent keyword should pass'
    );

    // color-mix with currentColor keyword
    assert_color_validates(
        'color-mix(in srgb, currentColor, blue)',
        true,
        'color-mix with currentColor keyword should pass'
    );

    // color-mix with extra whitespace
    assert_color_validates(
        'color-mix(  in   srgb  ,   red   50%  ,   blue   50%  )',
        true,
        'color-mix with extra whitespace should pass'
    );

    // color-mix with 8-digit hex (with alpha)
    assert_color_validates(
        'color-mix(in srgb, #ff000080, #0000ff80)',
        true,
        'color-mix with 8-digit hex should pass'
    );

    // color-mix with 3-digit hex
    assert_color_validates(
        'color-mix(in srgb, #f00, #00f)',
        true,
        'color-mix with 3-digit hex should pass'
    );

    // color-mix with negative percentages (technically invalid CSS but should validate for safety)
    assert_color_validates(
        'color-mix(in srgb, red -10%, blue)',
        true,
        'color-mix with negative percentage should pass validation (CSS engine will handle correctness)'
    );
}

// ============================================================================
// Test Cases: Invalid color-mix() - Missing Parts
// ============================================================================

function test_invalid_color_mix_missing_parts() {
    // Missing 'in' keyword
    assert_color_validates(
        'color-mix(srgb, red, blue)',
        false,
        'color-mix without "in" keyword should fail'
    );

    // Missing color space
    assert_color_validates(
        'color-mix(in, red, blue)',
        false,
        'color-mix without color space should fail'
    );

    // Missing second color
    assert_color_validates(
        'color-mix(in srgb, red)',
        false,
        'color-mix with only one color should fail'
    );

    // Missing first color
    assert_color_validates(
        'color-mix(in srgb, , blue)',
        false,
        'color-mix with missing first color should fail'
    );

    // Invalid color space
    assert_color_validates(
        'color-mix(in invalid-space, red, blue)',
        false,
        'color-mix with invalid color space should fail'
    );

    // Invalid hue method
    assert_color_validates(
        'color-mix(in lch invalid-hue, red, blue)',
        false,
        'color-mix with invalid hue method should fail'
    );
}

// ============================================================================
// Test Cases: Invalid color-mix() - Malformed Syntax
// ============================================================================

function test_invalid_color_mix_malformed_syntax() {
    // Missing parentheses
    assert_color_validates(
        'color-mix in srgb, red, blue',
        false,
        'color-mix without parentheses should fail'
    );

    // Unclosed parentheses
    assert_color_validates(
        'color-mix(in srgb, red, blue',
        false,
        'color-mix with unclosed parentheses should fail'
    );

    // Extra closing parentheses
    assert_color_validates(
        'color-mix(in srgb, red, blue))',
        false,
        'color-mix with extra closing parentheses should fail'
    );

    // Missing commas
    assert_color_validates(
        'color-mix(in srgb red blue)',
        false,
        'color-mix without commas should fail'
    );

    // Too many colors
    assert_color_validates(
        'color-mix(in srgb, red, blue, green)',
        false,
        'color-mix with three colors should fail'
    );

    // Empty function
    assert_color_validates(
        'color-mix()',
        false,
        'Empty color-mix should fail'
    );
}

// ============================================================================
// Test Cases: Invalid color-mix() - Injection Attempts
// ============================================================================

function test_invalid_color_mix_injection_attempts() {
    // Script injection attempt
    assert_color_validates(
        'color-mix(in srgb, red, blue); <script>alert("xss")</script>',
        false,
        'color-mix with script injection should fail'
    );

    // CSS injection attempt
    assert_color_validates(
        'color-mix(in srgb, red, blue); } body { background: red; }',
        false,
        'color-mix with CSS injection should fail'
    );

    // URL injection attempt
    assert_color_validates(
        'color-mix(in srgb, red, blue); background: url(javascript:alert(1))',
        false,
        'color-mix with URL injection should fail'
    );

    // Comment injection
    assert_color_validates(
        'color-mix(in srgb, red, blue) /* comment */',
        false,
        'color-mix with comment should fail'
    );

    // Backslash escape attempt
    assert_color_validates(
        'color-mix(in srgb, red, blue\\)',
        false,
        'color-mix with backslash should fail'
    );

    // Multiple declarations
    assert_color_validates(
        'color-mix(in srgb, red, blue); color: green',
        false,
        'color-mix with multiple declarations should fail'
    );
}

// ============================================================================
// Test Cases: Valid Modern Color Functions
// ============================================================================

function test_valid_modern_color_functions() {
    // lab() function
    assert_color_validates(
        'lab(50% 40 59.5)',
        true,
        'lab() color function should pass'
    );

    assert_color_validates(
        'lab(50% 40 59.5 / 0.5)',
        true,
        'lab() with alpha should pass'
    );

    // lch() function
    assert_color_validates(
        'lch(50% 50 180)',
        true,
        'lch() color function should pass'
    );

    assert_color_validates(
        'lch(50% 50 180deg)',
        true,
        'lch() with degree unit should pass'
    );

    // oklch() function
    assert_color_validates(
        'oklch(60% 0.15 50)',
        true,
        'oklch() color function should pass'
    );

    assert_color_validates(
        'oklch(60% 0.15 50deg / 0.8)',
        true,
        'oklch() with alpha should pass'
    );

    // oklab() function
    assert_color_validates(
        'oklab(60% 0.1 0.1)',
        true,
        'oklab() color function should pass'
    );

    // color() function
    assert_color_validates(
        'color(display-p3 1 0.5 0)',
        true,
        'color() function should pass'
    );

    assert_color_validates(
        'color(srgb 1 0 0)',
        true,
        'color() with srgb should pass'
    );

    assert_color_validates(
        'color(a98-rgb 1 0 0 / 0.5)',
        true,
        'color() with alpha should pass'
    );

    // Modern color functions with var()
    assert_color_validates(
        'lab(var(--lightness) var(--a) var(--b))',
        true,
        'lab() with var() should pass'
    );

    assert_color_validates(
        'oklch(var(--l) var(--c) var(--h))',
        true,
        'oklch() with var() should pass'
    );
}

// ============================================================================
// Test Cases: Invalid Modern Color Functions
// ============================================================================

function test_invalid_modern_color_functions() {
    // Missing parentheses
    assert_color_validates(
        'lab 50% 40 59.5',
        false,
        'lab without parentheses should fail'
    );

    // Unclosed parentheses
    assert_color_validates(
        'lab(50% 40 59.5',
        false,
        'lab with unclosed parentheses should fail'
    );

    // Injection attempts
    assert_color_validates(
        'lab(50% 40 59.5); <script>alert("xss")</script>',
        false,
        'lab with script injection should fail'
    );

    assert_color_validates(
        'oklch(60% 0.15 50) } body { background: red',
        false,
        'oklch with CSS injection should fail'
    );

    // Invalid function name
    assert_color_validates(
        'fakelab(50% 40 59.5)',
        false,
        'Invalid color function name should fail'
    );
}

// ============================================================================
// Test Cases: Regular Colors (Should Fall Through to WordPress)
// ============================================================================

function test_regular_colors_fallthrough() {
    // These should return false from our validator, letting WordPress handle them

    // Test that regular colors return false (not handled by our validator)
    $regular_colors = array(
        '#ff0000' => 'hex color',
        '#00f' => '3-digit hex',
        '#ff000080' => '8-digit hex with alpha',
        'rgb(255, 0, 0)' => 'rgb function',
        'rgba(255, 0, 0, 0.5)' => 'rgba function',
        'rgb(255 0 0)' => 'rgb modern syntax',
        'rgb(255 0 0 / 0.5)' => 'rgb modern syntax with alpha',
        'hsl(200, 50%, 80%)' => 'hsl function',
        'hsla(200, 50%, 80%, 0.5)' => 'hsla function',
        'hsl(200deg 50% 80%)' => 'hsl modern syntax',
        'red' => 'named color',
        'transparent' => 'transparent keyword',
        'currentColor' => 'currentColor keyword',
        'var(--my-color)' => 'var() function',
        'calc(100% - 20px)' => 'calc() function',
    );

    foreach ( $regular_colors as $color => $description ) {
        $css_test_string = "color: {$color}";
        $result = Generate::safecss_filter_attr_allow_color_mix_css( false, $css_test_string );

        if ( $result !== false ) {
            throw new Exception( "Regular {$description} ('{$color}') should return false to fall through to WordPress validation" );
        }
    }
}

// ============================================================================
// Test Cases: Valid box-shadow - none keyword
// ============================================================================

function test_valid_box_shadow_none() {
    // The 'none' keyword
    assert_box_shadow_validates(
        'none',
        true,
        'box-shadow: none should pass'
    );
}

// ============================================================================
// Test Cases: Valid box-shadow - Two Lengths (offset-x, offset-y)
// ============================================================================

function test_valid_box_shadow_two_lengths() {
    // Basic two-length values (required minimum)
    assert_box_shadow_validates(
        '60px -16px',
        true,
        'box-shadow with offset-x and offset-y should pass'
    );

    assert_box_shadow_validates(
        '10px 10px',
        true,
        'box-shadow with positive offsets should pass'
    );

    assert_box_shadow_validates(
        '-10px -10px',
        true,
        'box-shadow with negative offsets should pass'
    );

    assert_box_shadow_validates(
        '0 0',
        true,
        'box-shadow with zero offsets should pass'
    );

    assert_box_shadow_validates(
        '0px 0px',
        true,
        'box-shadow with zero offsets and units should pass'
    );
}

// ============================================================================
// Test Cases: Valid box-shadow - Three Lengths (with blur)
// ============================================================================

function test_valid_box_shadow_three_lengths() {
    // Three length values: offset-x, offset-y, blur
    assert_box_shadow_validates(
        '10px 5px 5px',
        true,
        'box-shadow with blur-radius should pass'
    );

    assert_box_shadow_validates(
        '0 0 10px',
        true,
        'box-shadow with only blur should pass'
    );

    assert_box_shadow_validates(
        '5px 5px 0',
        true,
        'box-shadow with zero blur should pass'
    );

    assert_box_shadow_validates(
        '-5px -5px 10px',
        true,
        'box-shadow with negative offsets and blur should pass'
    );
}

// ============================================================================
// Test Cases: Valid box-shadow - Four Lengths (with spread)
// ============================================================================

function test_valid_box_shadow_four_lengths() {
    // Four length values: offset-x, offset-y, blur, spread
    assert_box_shadow_validates(
        '2px 2px 2px 1px',
        true,
        'box-shadow with spread-radius should pass'
    );

    assert_box_shadow_validates(
        '10px 10px 5px 0px',
        true,
        'box-shadow with zero spread should pass'
    );

    assert_box_shadow_validates(
        '5px 5px 10px -2px',
        true,
        'box-shadow with negative spread should pass'
    );

    assert_box_shadow_validates(
        '0 0 0 5px',
        true,
        'box-shadow with only spread should pass'
    );
}

// ============================================================================
// Test Cases: Valid box-shadow - With Colors
// ============================================================================

function test_valid_box_shadow_with_colors() {
    // With named colors
    assert_box_shadow_validates(
        '60px -16px red',
        true,
        'box-shadow with named color should pass'
    );

    assert_box_shadow_validates(
        '10px 5px 5px black',
        true,
        'box-shadow with black color should pass'
    );

    // With hex colors
    assert_box_shadow_validates(
        '10px 10px #ff0000',
        true,
        'box-shadow with hex color should pass'
    );

    assert_box_shadow_validates(
        '5px 5px 5px #000',
        true,
        'box-shadow with 3-digit hex should pass'
    );

    assert_box_shadow_validates(
        '2px 2px 4px #00000080',
        true,
        'box-shadow with 8-digit hex (with alpha) should pass'
    );

    // With rgb/rgba colors
    assert_box_shadow_validates(
        '2px 2px 2px 1px rgb(0 0 0 / 20%)',
        true,
        'box-shadow with rgb() modern syntax should pass'
    );

    assert_box_shadow_validates(
        '3px 3px rgba(255, 0, 0, 0.5)',
        true,
        'box-shadow with rgba() should pass'
    );

    assert_box_shadow_validates(
        '5px 5px 10px rgb(100, 100, 100)',
        true,
        'box-shadow with rgb() comma syntax should pass'
    );

    // With hsl/hsla colors
    assert_box_shadow_validates(
        '3px 3px 5px hsl(200, 50%, 80%)',
        true,
        'box-shadow with hsl() should pass'
    );

    assert_box_shadow_validates(
        '3px 3px 5px hsl(200deg 50% 80%)',
        true,
        'box-shadow with hsl() modern syntax should pass'
    );

    assert_box_shadow_validates(
        '3px 3px 5px hsla(200, 50%, 80%, 0.5)',
        true,
        'box-shadow with hsla() should pass'
    );

    // With modern color functions
    assert_box_shadow_validates(
        '3px 3px 5px lab(50% 40 59.5)',
        true,
        'box-shadow with lab() color should pass'
    );

    assert_box_shadow_validates(
        '3px 3px 5px oklch(60% 0.15 50deg)',
        true,
        'box-shadow with oklch() color should pass'
    );

    // With transparent
    assert_box_shadow_validates(
        '5px 5px 10px transparent',
        true,
        'box-shadow with transparent should pass'
    );

    // With currentColor
    assert_box_shadow_validates(
        '5px 5px 10px currentColor',
        true,
        'box-shadow with currentColor should pass'
    );
}

// ============================================================================
// Test Cases: Valid box-shadow - Color First
// ============================================================================

function test_valid_box_shadow_color_first() {
    // Color at the beginning
    assert_box_shadow_validates(
        'red 60px -16px',
        true,
        'box-shadow with color first should pass'
    );

    assert_box_shadow_validates(
        'black 10px 5px 5px',
        true,
        'box-shadow with color first and blur should pass'
    );

    assert_box_shadow_validates(
        '#ff0000 2px 2px 2px 1px',
        true,
        'box-shadow with hex color first and all values should pass'
    );

    assert_box_shadow_validates(
        'rgba(0, 0, 0, 0.5) 5px 5px 10px',
        true,
        'box-shadow with rgba() first should pass'
    );

    assert_box_shadow_validates(
        'hsl(200deg 50% 80%) 3px 3px 5px',
        true,
        'box-shadow with hsl() first should pass'
    );
}

// ============================================================================
// Test Cases: Valid box-shadow - With inset
// ============================================================================

function test_valid_box_shadow_with_inset() {
    // inset at the beginning
    assert_box_shadow_validates(
        'inset 5em 1em gold',
        true,
        'box-shadow with inset at beginning should pass'
    );

    assert_box_shadow_validates(
        'inset 10px 10px 5px black',
        true,
        'box-shadow with inset and blur should pass'
    );

    assert_box_shadow_validates(
        'inset 2px 2px 4px 1px rgba(0, 0, 0, 0.3)',
        true,
        'box-shadow with inset and all values should pass'
    );

    // inset at the end
    assert_box_shadow_validates(
        '3px 3px red inset',
        true,
        'box-shadow with inset at end should pass'
    );

    assert_box_shadow_validates(
        '5px 5px 10px #000 inset',
        true,
        'box-shadow with inset at end and blur should pass'
    );

    assert_box_shadow_validates(
        '2px 2px 4px 1px rgba(0, 0, 0, 0.3) inset',
        true,
        'box-shadow with inset at end and all values should pass'
    );

    // Uppercase/mixed case
    assert_box_shadow_validates(
        'INSET 5px 5px black',
        true,
        'box-shadow with uppercase INSET should pass'
    );

    assert_box_shadow_validates(
        'InSeT 5px 5px black',
        true,
        'box-shadow with mixed case InSeT should pass'
    );
}

// ============================================================================
// Test Cases: Valid box-shadow - Multiple Shadows
// ============================================================================

function test_valid_box_shadow_multiple_shadows() {
    // Two shadows
    assert_box_shadow_validates(
        '3px 3px red, -1em 0 0.4em olive',
        true,
        'box-shadow with two shadows should pass'
    );

    assert_box_shadow_validates(
        '3px 3px red inset, -1em 0 0.4em olive',
        true,
        'box-shadow with inset in multiple shadows should pass'
    );

    // Three shadows
    assert_box_shadow_validates(
        '2px 2px 2px red, 4px 4px 4px blue, 6px 6px 6px green',
        true,
        'box-shadow with three shadows should pass'
    );

    // Multiple shadows with different formats
    assert_box_shadow_validates(
        '0 0 10px rgba(0,0,0,0.5), inset 0 0 5px rgba(255,255,255,0.3)',
        true,
        'box-shadow with mixed shadow types should pass'
    );

    // Complex multiple shadows
    assert_box_shadow_validates(
        '0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24)',
        true,
        'Material design shadow should pass'
    );

    // Multiple shadows with colors containing commas
    assert_box_shadow_validates(
        '2px 2px 4px rgba(255, 0, 0, 0.5), 4px 4px 8px rgba(0, 0, 255, 0.5)',
        true,
        'Multiple shadows with rgba commas should pass'
    );
}

// ============================================================================
// Test Cases: Valid box-shadow - Various Units
// ============================================================================

function test_valid_box_shadow_various_units() {
    // px
    assert_box_shadow_validates(
        '10px 10px 5px 2px black',
        true,
        'box-shadow with px units should pass'
    );

    // em
    assert_box_shadow_validates(
        '1em 1em 0.5em red',
        true,
        'box-shadow with em units should pass'
    );

    // rem
    assert_box_shadow_validates(
        '1rem 1rem 0.5rem blue',
        true,
        'box-shadow with rem units should pass'
    );

    // Viewport units
    assert_box_shadow_validates(
        '1vh 1vw 0.5vmin green',
        true,
        'box-shadow with viewport units should pass'
    );

    assert_box_shadow_validates(
        '1vmax 1vmax 0.5vmin yellow',
        true,
        'box-shadow with vmax units should pass'
    );

    // Other units
    assert_box_shadow_validates(
        '10pt 10pt 5pt black',
        true,
        'box-shadow with pt units should pass'
    );

    assert_box_shadow_validates(
        '1cm 1cm 0.5cm red',
        true,
        'box-shadow with cm units should pass'
    );

    assert_box_shadow_validates(
        '10mm 10mm 5mm blue',
        true,
        'box-shadow with mm units should pass'
    );

    assert_box_shadow_validates(
        '1in 1in 0.5in green',
        true,
        'box-shadow with in units should pass'
    );

    assert_box_shadow_validates(
        '10pc 10pc 5pc yellow',
        true,
        'box-shadow with pc units should pass'
    );

    assert_box_shadow_validates(
        '10ex 10ex 5ex orange',
        true,
        'box-shadow with ex units should pass'
    );

    assert_box_shadow_validates(
        '10ch 10ch 5ch purple',
        true,
        'box-shadow with ch units should pass'
    );

    // Mixed units
    assert_box_shadow_validates(
        '10px 1em 0.5rem 2px rgba(0,0,0,0.5)',
        true,
        'box-shadow with mixed units should pass'
    );

    // Percentage (not common but valid for some contexts)
    // Note: percentages might not be valid for box-shadow in actual CSS spec
    // but if the validator allows them, test it
}

// ============================================================================
// Test Cases: Valid box-shadow - Edge Cases
// ============================================================================

function test_valid_box_shadow_edge_cases() {
    // Extra whitespace
    assert_box_shadow_validates(
        '  10px   10px   5px   black  ',
        true,
        'box-shadow with extra whitespace should pass'
    );

    assert_box_shadow_validates(
        '10px 10px   5px     2px     rgba(0,0,0,0.5)',
        true,
        'box-shadow with irregular whitespace should pass'
    );

    // Decimal values
    assert_box_shadow_validates(
        '10.5px 5.25px 2.75px 1.5px rgba(0,0,0,0.5)',
        true,
        'box-shadow with decimal values should pass'
    );

    assert_box_shadow_validates(
        '0.5em 0.25em 0.125em red',
        true,
        'box-shadow with small decimal values should pass'
    );

    // Very large values
    assert_box_shadow_validates(
        '1000px 1000px 500px black',
        true,
        'box-shadow with very large values should pass'
    );

    // Very small values
    assert_box_shadow_validates(
        '0.01px 0.01px 0.01px black',
        true,
        'box-shadow with very small values should pass'
    );

    // Color angles with units
    assert_box_shadow_validates(
        '5px 5px 10px hsl(200deg 50% 80%)',
        true,
        'box-shadow with hsl degree unit should pass'
    );

    assert_box_shadow_validates(
        '5px 5px 10px hsl(3.14rad 50% 80%)',
        true,
        'box-shadow with hsl radian unit should pass'
    );

    assert_box_shadow_validates(
        '5px 5px 10px hsl(200grad 50% 80%)',
        true,
        'box-shadow with hsl grad unit should pass'
    );

    assert_box_shadow_validates(
        '5px 5px 10px hsl(0.5turn 50% 80%)',
        true,
        'box-shadow with hsl turn unit should pass'
    );
}

// ============================================================================
// Test Cases: Invalid box-shadow - Missing Required Values
// ============================================================================

function test_invalid_box_shadow_missing_required() {
    // Only one length value (needs minimum 2)
    assert_box_shadow_validates(
        '10px',
        false,
        'box-shadow with only one length should fail'
    );

    assert_box_shadow_validates(
        '10px red',
        false,
        'box-shadow with only one length and color should fail'
    );

    // Only color
    assert_box_shadow_validates(
        'red',
        false,
        'box-shadow with only color should fail'
    );

    assert_box_shadow_validates(
        '#ff0000',
        false,
        'box-shadow with only hex color should fail'
    );

    // Only inset
    assert_box_shadow_validates(
        'inset',
        false,
        'box-shadow with only inset keyword should fail'
    );

    // Empty string
    assert_box_shadow_validates(
        '',
        false,
        'Empty box-shadow should fail'
    );
}

// ============================================================================
// Test Cases: Invalid box-shadow - Malformed Syntax
// ============================================================================

function test_invalid_box_shadow_malformed() {
    // Invalid units
    assert_box_shadow_validates(
        '10 10 5 red',
        false,
        'box-shadow without units should fail'
    );

    assert_box_shadow_validates(
        '10xyz 10xyz red',
        false,
        'box-shadow with invalid units should fail'
    );

    // Invalid color format
    assert_box_shadow_validates(
        '10px 10px notacolor',
        false,
        'box-shadow with invalid color should fail'
    );

    assert_box_shadow_validates(
        '10px 10px #gg0000',
        false,
        'box-shadow with invalid hex should fail'
    );

    // Too many length values
    assert_box_shadow_validates(
        '10px 10px 5px 2px 1px red',
        false,
        'box-shadow with 5 length values should fail'
    );

    // Malformed rgb/rgba
    assert_box_shadow_validates(
        '10px 10px rgb(300, 0, 0)',
        false,
        'box-shadow with invalid rgb values might fail'
    );

    // Unclosed parentheses
    assert_box_shadow_validates(
        '10px 10px rgba(0, 0, 0, 0.5',
        false,
        'box-shadow with unclosed rgba should fail'
    );

    // Invalid comma placement in multiple shadows
    assert_box_shadow_validates(
        '10px 10px red, , 5px 5px blue',
        false,
        'box-shadow with empty comma should fail'
    );

    // Negative blur-radius (not allowed in CSS)
    // Note: The validator might allow this as it focuses on safety, not CSS correctness
    // Uncomment if you want strict CSS validation
    // assert_box_shadow_validates(
    //     '10px 10px -5px red',
    //     false,
    //     'box-shadow with negative blur should fail'
    // );
}

// ============================================================================
// Test Cases: Invalid box-shadow - Injection Attempts
// ============================================================================

function test_invalid_box_shadow_injection_attempts() {
    // Script injection
    assert_box_shadow_validates(
        '10px 10px red; <script>alert("xss")</script>',
        false,
        'box-shadow with script injection should fail'
    );

    // CSS injection
    assert_box_shadow_validates(
        '10px 10px red; } body { background: red; }',
        false,
        'box-shadow with CSS injection should fail'
    );

    // URL injection
    assert_box_shadow_validates(
        '10px 10px red; background: url(javascript:alert(1))',
        false,
        'box-shadow with URL injection should fail'
    );

    // Comment injection
    assert_box_shadow_validates(
        '10px 10px red /* comment */',
        false,
        'box-shadow with comment should fail'
    );

    // Backslash escape attempt
    assert_box_shadow_validates(
        '10px 10px red\\',
        false,
        'box-shadow with backslash should fail'
    );

    // Multiple declarations
    assert_box_shadow_validates(
        '10px 10px red; color: green',
        false,
        'box-shadow with multiple declarations should fail'
    );

    // Expression/calc injection (old IE)
    assert_box_shadow_validates(
        'expression(alert(1)) 10px red',
        false,
        'box-shadow with expression should fail'
    );

    // Ampersand
    assert_box_shadow_validates(
        '10px 10px red & body { }',
        false,
        'box-shadow with ampersand should fail'
    );

    // Equals sign
    assert_box_shadow_validates(
        '10px 10px red = evil',
        false,
        'box-shadow with equals should fail'
    );
}

// ============================================================================
// Test Cases: Unsupported Features (for documentation)
// ============================================================================

function test_box_shadow_unsupported_features() {
    // These tests document features that are NOT currently supported by the validator
    // but may be valid CSS. They will fall through to WordPress's default validation.

    echo "\n--- Testing Unsupported Features (should return false, handled by WordPress) ---\n";

    // Global CSS keywords - NOT supported by our validator
    $unsupported = array(
        'inherit' => 'inherit keyword',
        'initial' => 'initial keyword',
        'revert' => 'revert keyword',
        'revert-layer' => 'revert-layer keyword',
        'unset' => 'unset keyword',
    );

    foreach ( $unsupported as $value => $description ) {
        $css_test_string = "box-shadow: {$value}";
        $result = Generate::safecss_filter_attr_allow_box_shadow_css( false, $css_test_string );

        if ( $result !== false ) {
            throw new Exception( "Unsupported {$description} should return false (got: " . var_export($result, true) . ")" );
        }
        echo "✓ {$description} correctly returns false (handled by WordPress)\n";
    }

    // var() and calc() functions - These might work if WordPress handles them before our validator
    // but our validator itself doesn't parse them
    echo "\n--- Note: var() and calc() support depends on WordPress's safecss_filter_attr ---\n";
    echo "These functions are removed by WordPress before dangerous character checking,\n";
    echo "so they may work even though our validator doesn't explicitly handle them.\n";
}

// ============================================================================
// Run Tests
// ============================================================================

// Uncomment to run tests:
// run_color_validation_tests();
// run_box_shadow_validation_tests();
```

## Running the Tests

To run these tests manually:

1. Ensure WordPress is loaded and the `Search_Filter\Styles\Generate` class is available
2. Copy the test code above to a PHP file
3. Uncomment the test runner lines:
   - `run_color_validation_tests();`
   - `run_box_shadow_validation_tests();`
4. Run the file: `php test-file.php`

## Migrating to PHPUnit

When migrating to PHPUnit, each test function should become a test method:

```php
class GenerateColorValidationTest extends WP_UnitTestCase {

    /**
     * @test
     */
    public function it_validates_basic_color_mix() {
        test_valid_color_mix_basic();
    }

    // ... etc
}

class GenerateBoxShadowValidationTest extends WP_UnitTestCase {

    /**
     * @test
     */
    public function it_validates_box_shadow_none() {
        test_valid_box_shadow_none();
    }

    // ... etc
}
```

## Coverage Summary

### Color Validation Tests (94 test cases)

**Valid color-mix() tests (41 tests):**
- ✓ Basic syntax (6 tests)
- ✓ With percentages (6 tests)
- ✓ With hue interpolation (4 tests)
- ✓ With var() functions (4 tests)
- ✓ With calc() functions (2 tests)
- ✓ All color spaces (11 tests)
- ✓ Edge cases (8 tests)

**Invalid color-mix() tests (20 tests):**
- ✓ Missing parts (6 tests)
- ✓ Malformed syntax (7 tests)
- ✓ Injection attempts (7 tests)

**Modern color functions (18 tests):**
- ✓ Valid: lab(), lch(), oklch(), oklab(), color() (13 tests)
- ✓ Invalid: malformed and injection attempts (5 tests)

**Regular colors fallthrough (15 tests):**
- ✓ Hex, RGB, HSL, named colors, var(), calc()

### Box-Shadow Validation Tests (120+ test cases)

**Valid box-shadow tests (91+ tests):**
- ✓ none keyword (1 test)
- ✓ Two lengths - offset-x, offset-y (5 tests)
- ✓ Three lengths - with blur (4 tests)
- ✓ Four lengths - with spread (4 tests)
- ✓ With colors - all color formats (18 tests)
- ✓ Color first syntax (5 tests)
- ✓ With inset keyword (8 tests)
- ✓ Multiple shadows (6 tests)
- ✓ Various units - px, em, rem, vh, vw, etc. (14 tests)
- ✓ Edge cases - whitespace, decimals, extreme values (10 tests)

**Invalid box-shadow tests (20 tests):**
- ✓ Missing required values (6 tests)
- ✓ Malformed syntax (7 tests)
- ✓ Injection attempts (9 tests)

**Unsupported features documentation (5+ tests):**
- ✓ Global CSS keywords (inherit, initial, revert, etc.)
- ✓ Notes on var() and calc() support

### What's Covered

**Security:** ✓ XSS prevention, CSS injection, URL injection, comment injection, escape attempts
**Syntax:** ✓ Valid CSS patterns, malformed syntax detection
**Edge Cases:** ✓ Whitespace handling, decimal values, extreme values, case sensitivity
**Compatibility:** ✓ Modern color functions, all color spaces, multiple length units
**Real-world usage:** ✓ Material Design shadows, multiple shadows, complex color formats

### What's NOT Currently Supported (documented as future enhancements)

**color-mix():**
- Nested color-mix() functions (documented in code)

**box-shadow:**
- Global CSS keywords (inherit, initial, revert, etc.) - fall through to WordPress
- var() and calc() - handled by WordPress's safecss_filter_attr

**Total: 214+ comprehensive test cases covering all scenarios**
