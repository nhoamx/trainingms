# Quiz Components

This directory contains reusable Vue components for the quiz functionality, extracted from `Take.vue` and `TakeReduced.vue` to improve maintainability and code reuse.

## Components Overview

### Core Components

#### `ProgressBar.vue`
- **Purpose**: Displays quiz progress with customizable content
- **Props**: `progress` (Number) - Progress percentage (0-100)
- **Slots**: Default slot for progress description text
- **Usage**: Shows current section progress in both quiz types

#### `ViewModeToggle.vue`
- **Purpose**: Toggle between comfortable and compact view modes
- **Props**: `modelValue` (String) - Current view mode
- **Emits**: `update:modelValue` - When view mode changes
- **Usage**: Provides consistent view mode switching across quiz types

### Data Collection Components

#### `PersonalDataSection.vue`
- **Purpose**: Collects personal demographic information
- **Props**: 
  - `modelValue` (Object) - Current personal data answers
  - `referenceData` (Object) - Available options from quiz configuration
- **Emits**: `update:modelValue` - When personal data changes
- **Fields**: Sex, Age, Civil Status, Education Level

#### `LaborDataSection.vue`
- **Purpose**: Collects work-related information
- **Props**:
  - `modelValue` (Object) - Current labor data answers
  - `laboralData` (Object) - Available labor options
  - `organization` (Object) - Organization-specific positions and departments
- **Emits**: `update:modelValue` - When labor data changes
- **Fields**: Position, Department, Work type, Contract type, etc.

### Question Components

#### `TraumaticEventsSection.vue`
- **Purpose**: Displays traumatic events questions with yes/no answers
- **Props**:
  - `title` (String) - Section title
  - `questions` (Object/Array) - List of traumatic event questions
  - `modelValue` (Object) - Current answers
  - `answerOptions` (Array) - Yes/No options
  - `namePrefix` (String) - Prefix for radio button names
- **Emits**: `update:modelValue` - When answers change

#### `FollowUpQuestionsSection.vue`
- **Purpose**: Displays follow-up questions based on previous traumatic event answers
- **Props**:
  - `followUpQuestions` (Object) - Categorized follow-up questions
  - `modelValue` (Object) - Current answers
  - `answerOptions` (Array) - Yes/No options
- **Emits**: `update:modelValue` - When answers change

#### `GeneralQuestionsSection.vue`
- **Purpose**: Displays paginated general questions with 5-point scale
- **Props**:
  - `paginatedQuestions` (Array) - Current page questions
  - `modelValue` (Object) - Current answers
  - `answerOptions` (Array) - 5-point scale options
  - `viewMode` (String) - Display mode (comfortable/compact)
- **Emits**: `update:modelValue` - When answers change

#### `ConditionalQuestionsSection.vue`
- **Purpose**: Displays conditional questions that appear based on yes/no conditions
- **Props**:
  - `conditionalSections` (Object) - Conditional question sections
  - `modelValue` (Object) - Current answers
  - `yesNoOptions` (Array) - Yes/No options for conditions
  - `generalOptions` (Array) - 5-point scale options for questions
- **Emits**: `update:modelValue` - When answers change

### Navigation Components

#### `NavigationButtons.vue`
- **Purpose**: Provides consistent navigation controls across quiz sections
- **Props**:
  - `showPrevious` (Boolean) - Show previous button
  - `showNext` (Boolean) - Show next button
  - `showSubmit` (Boolean) - Show submit button
  - `isLastSection` (Boolean) - Whether this is the final section
  - `previousLabel`, `nextLabel`, `submitLabel` (String) - Button labels
  - `nextDisabled`, `submitDisabled` (Boolean) - Button states
  - `isSubmitting` (Boolean) - Show loading state
- **Emits**: `previous`, `next`, `submit` - Navigation events

## Usage Examples

### Basic Import and Usage

```vue
<script setup>
import {
  ProgressBar,
  PersonalDataSection,
  NavigationButtons
} from '@/Components/Quiz';

// Or individual imports
import ProgressBar from '@/Components/Quiz/ProgressBar.vue';
</script>

<template>
  <ProgressBar :progress="50">
    Section 1 of 2: Personal Information
  </ProgressBar>
  
  <PersonalDataSection 
    v-model="personalData" 
    :reference-data="quizData.reference_v"
  />
  
  <NavigationButtons
    :show-previous="currentStep > 0"
    :show-next="!isLastStep"
    :show-submit="isLastStep"
    @previous="previousStep"
    @next="nextStep"
    @submit="submitQuiz"
  />
</template>
```

## Component Architecture Benefits

1. **Reusability**: Components can be shared between `Take.vue` and `TakeReduced.vue`
2. **Maintainability**: Single source of truth for each UI pattern
3. **Testability**: Components can be tested in isolation
4. **Consistency**: Ensures consistent behavior across quiz types
5. **Flexibility**: Props allow customization while maintaining consistency

## Data Flow

All components follow Vue 3's composition API patterns:
- Use `v-model` for two-way data binding where applicable
- Emit events for parent component communication
- Props for configuration and data input
- Computed properties for derived state

## Styling

Components use Tailwind CSS classes consistent with the existing design system:
- Slate color palette for neutral elements
- Consistent spacing and typography
- Responsive design patterns
- Hover and focus states for accessibility
