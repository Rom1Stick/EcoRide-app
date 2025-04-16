module.exports = {
  extends: [
    "stylelint-config-standard-scss"
  ],
  plugins: [
    "stylelint-scss"
  ],
  rules: {
    "selector-class-pattern": "^[a-z]([\\-]?[a-z0-9]+)*(?:__[a-z0-9]([\\-]?[a-z0-9]+)*)?(?:--[a-z0-9]([\\-]?[a-z0-9]+)*)?$",
    "max-nesting-depth": 2,
    "selector-max-id": 0,
    "declaration-no-important": true,
    "property-disallowed-list": ["outline"],
    "declaration-property-value-disallowed-list": {
      "/^outline$/": ["/none/"]
    },
    "unit-allowed-list": ["em", "rem", "%", "vh", "vw", "px", "deg", "s"],
    "font-size-range": {
      "min": "12px",
      "max": "48px"
    },
    "color-no-invalid-hex": true,
    "color-named": "never",
    "color-hex-length": "short",
    "function-url-no-scheme-relative": true,
    "no-descending-specificity": null,
    "value-keyword-case": ["lower", {
      "ignoreKeywords": ["/^#[A-Fa-f0-9]{3,6}$/"]
    }],
    "scss/at-rule-no-unknown": true,
    "media-feature-name-no-unknown": true,
    "declaration-block-no-duplicate-properties": [true, {
      "ignore": ["consecutive-duplicates-with-different-values"]
    }],
    "declaration-block-single-line-max-declarations": 1,
    "no-eol-whitespace": true,
    "block-no-empty": true,
    "color-hex-case": "lower",
    "length-zero-no-unit": true,
    "selector-pseudo-class-no-unknown": true,
    "selector-pseudo-element-no-unknown": true
  }
}; 