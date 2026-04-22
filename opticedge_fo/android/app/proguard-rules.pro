# google_mlkit_text_recognition references optional script-specific recognizers that are
# not included in the default ML Kit dependency. R8 reports them as missing unless suppressed.
-dontwarn com.google.mlkit.vision.text.chinese.**
-dontwarn com.google.mlkit.vision.text.devanagari.**
-dontwarn com.google.mlkit.vision.text.japanese.**
-dontwarn com.google.mlkit.vision.text.korean.**
