UPDATE narro_context_info SET has_suggestions=(SELECT COUNT(suggestion_id) FROM narro_suggestion, narro_context WHERE narro_suggestion.text_id=narro_context.text_id AND narro_context.context_id=narro_context_info.context_id AND narro_suggestion.language_id=narro_context_info.language_id);
UPDATE narro_context_info SET has_suggestions=1 WHERE has_suggestions>1;