<template>
  <Dashboard>
    <div class="py-8">
      <div class="mx-auto px-4 sm:px-6 lg:px-8">
        <nav class="mb-6 flex items-center gap-2 text-sm text-gray-500" aria-label="Breadcrumb">
          <Link :href="route('my-work-centers')" class="hover:text-gray-700 transition-colors">
            {{ t('My Work Centers') }}
          </Link>
          <span aria-hidden="true">/</span>
          <span class="font-medium text-gray-700">{{ workCenter.name }}</span>
          <span aria-hidden="true">/</span>
          <span class="font-medium text-teal-700">{{ t('Work Climate') }}</span>
        </nav>

        <div class="mb-8">
          <div class="flex flex-col sm:flex-row sm:items-center gap-6 mb-4">
            <div v-if="dashboardData.organization.logo" class="flex-shrink-0">
              <img
                :src="dashboardData.organization.logo"
                :alt="`${dashboardData.organization.name} logo`"
                class="h-20 w-auto object-contain max-w-xs"
              />
            </div>
            <div v-else class="flex-shrink-0">
              <h1 class="text-4xl font-bold text-gray-900">{{ workCenter.name }}</h1>
              <p class="mt-1 text-gray-500 text-sm">{{ dashboardData.organization.name }}</p>
              <p class="mt-1 text-gray-600">{{ t('Organizational Climate Assessment') }}</p>
            </div>
            <div class="sm:ml-auto">
              <LanguageSwitcher />
            </div>
          </div>
        </div>

        <div v-if="canManageClima" class="mb-5 flex items-center justify-between rounded-lg border border-amber-200 bg-amber-50 px-4 py-3">
          <div class="flex items-center gap-2 text-sm text-amber-800">
            <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
            <span class="font-medium">
              {{ isPreviewMode ? t('Previewing as organization user') : t('Admin Mode — content editing enabled') }}
            </span>
          </div>
          <button
            @click="isPreviewMode = !isPreviewMode"
            :class="[
              'rounded-lg px-4 py-1.5 text-sm font-semibold transition-all',
              isPreviewMode
                ? 'bg-amber-600 text-white hover:bg-amber-700'
                : 'border border-amber-300 text-amber-800 hover:bg-amber-100',
            ]"
          >
            {{ isPreviewMode ? t('Exit preview') : t('Preview as user') }}
          </button>
        </div>

        <div class="mb-8">
          <nav class="flex flex-wrap gap-2 lg:gap-4" aria-label="Tabs">
            <button
              v-for="tab in translatedTabs"
              :key="tab.key"
              @click="activeTab = tab.key"
              :class="[
                'px-4 sm:px-6 py-3 sm:py-4 rounded-lg font-semibold text-sm sm:text-base transition-all duration-200',
                activeTab === tab.key
                  ? 'bg-teal-600 text-white shadow-lg hover:bg-teal-700'
                  : 'bg-gray-100 text-gray-700 hover:bg-gray-200 hover:text-gray-900',
              ]"
              :aria-current="activeTab === tab.key ? 'page' : undefined"
            >
              {{ tab.label }}
            </button>
          </nav>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
          <div class="p-6 sm:p-8">
            <div v-show="activeTab === 'demographic'" class="animate-fade-in">
              <DemographicDataTab
                :demographic-details="dashboardData.demographic_details"
                :evaluations="evaluations"
              />
            </div>

            <div v-show="activeTab === 'results'" class="animate-fade-in space-y-8">
              <ClimaLaboralResultsTab
                :evaluations="evaluations"
                :organization-id="dashboardData.organization.id"
                :work-center-id="effectiveCanManage ? workCenter.id : ''"
              />


              <div class="border-t border-gray-200"></div>

              <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-8 border border-blue-100">
                <div class="max-w-2xl mx-auto text-center">
                  <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-100 rounded-full mb-4">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                  </div>
                  <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ t('Work Climate Report') }}</h3>
                  <p class="text-gray-600 mb-6">{{ t('View the detailed analysis of the evaluation results') }}</p>
                  <a
                    :href="`/organization/${dashboardData.organization.id}/likert/report`"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-all duration-200 shadow-md hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                  >
                    <span>{{ t('View Report') }}</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                    </svg>
                  </a>
                </div>
              </div>
            </div>

            <div v-show="activeTab === 'analysis'" class="animate-fade-in space-y-6">
              <!-- Top Risk Factors with Demographic Filters -->
              <div class="overflow-hidden rounded-xl border border-gray-200">
                <div class="border-b border-gray-200 bg-gray-50 p-5">
                  <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-900">{{ t('Demographic Filters') }}</h3>
                    <button
                      @click="resetAnalysisFilters"
                      class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-600 transition-colors hover:bg-gray-100"
                    >
                      ↺ {{ t('Reset Filters') }}
                    </button>
                  </div>
                  <div class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-5">
                    <div>
                      <label class="mb-1 block text-xs font-medium text-gray-600">{{ t('Gender') }}</label>
                      <select v-model="analysisFilters.gender" class="w-full rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-sm text-gray-900 focus:border-teal-500 focus:ring-teal-500">
                        <option value="">{{ t('All') }}</option>
                        <option v-for="g in dashboardData.demographic_details.genders" :key="g" :value="g">{{ g }}</option>
                      </select>
                    </div>
                    <div>
                      <label class="mb-1 block text-xs font-medium text-gray-600">{{ t('Contract Type') }}</label>
                      <select v-model="analysisFilters.contract_type" class="w-full rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-sm text-gray-900 focus:border-teal-500 focus:ring-teal-500">
                        <option value="">{{ t('All') }}</option>
                        <option v-for="ct in dashboardData.demographic_details.contract_types" :key="ct" :value="ct">{{ ct }}</option>
                      </select>
                    </div>
                    <div>
                      <label class="mb-1 block text-xs font-medium text-gray-600">{{ t('Position') }}</label>
                      <select v-model="analysisFilters.position" class="w-full rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-sm text-gray-900 focus:border-teal-500 focus:ring-teal-500">
                        <option value="">{{ t('All') }}</option>
                        <option v-for="p in dashboardData.demographic_details.positions" :key="p" :value="p">{{ p }}</option>
                      </select>
                    </div>
                    <div>
                      <label class="mb-1 block text-xs font-medium text-gray-600">{{ t('Area') }}</label>
                      <select v-model="analysisFilters.area" class="w-full rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-sm text-gray-900 focus:border-teal-500 focus:ring-teal-500">
                        <option value="">{{ t('All') }}</option>
                        <option v-for="a in dashboardData.demographic_details.areas" :key="a" :value="a">{{ a }}</option>
                      </select>
                    </div>
                    <div>
                      <label class="mb-1 block text-xs font-medium text-gray-600">{{ t('Shift') }}</label>
                      <select v-model="analysisFilters.shift" class="w-full rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-sm text-gray-900 focus:border-teal-500 focus:ring-teal-500">
                        <option value="">{{ t('All') }}</option>
                        <option v-for="s in dashboardData.demographic_details.shifts" :key="s" :value="s">{{ s }}</option>
                      </select>
                    </div>
                  </div>
                </div>

                <div class="p-5">
                  <h3 class="mb-1 text-base font-semibold text-gray-900">{{ t('Top 3 Risk Factors') }}</h3>
                  <p class="mb-4 text-xs text-gray-500">
                    {{ t('Based on total disagreement responses') }} · {{ filteredAnalysisEvaluations.length }} {{ t('evaluations') }}
                  </p>

                  <div v-if="topRiskFactors.length > 0" class="overflow-x-auto">
                    <table class="w-full text-sm">
                      <thead class="border-y border-gray-200 bg-gray-50">
                        <tr>
                          <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-700">{{ t('Factor') }}</th>
                          <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide">
                            <span class="inline-block rounded-full bg-green-100 px-2.5 py-1 text-green-800">{{ t('Strongly Agree') }}</span>
                          </th>
                          <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide">
                            <span class="inline-block rounded-full bg-blue-100 px-2.5 py-1 text-blue-800">{{ t('Agree') }}</span>
                          </th>
                          <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide">
                            <span class="inline-block rounded-full bg-orange-100 px-2.5 py-1 text-orange-800">{{ t('Disagree') }}</span>
                          </th>
                          <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide">
                            <span class="inline-block rounded-full bg-red-100 px-2.5 py-1 text-red-800">{{ t('Strongly Disagree') }}</span>
                          </th>
                        </tr>
                      </thead>
                      <tbody class="divide-y divide-gray-100">
                        <tr v-for="(factor, index) in topRiskFactors" :key="factor.name" class="transition-colors hover:bg-gray-50">
                          <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                              <span
                                class="inline-flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full text-xs font-bold"
                                :class="riskFactorBadgeClass(index)"
                              >
                                {{ index + 1 }}
                              </span>
                              <span class="font-medium text-gray-900">{{ factor.name }}</span>
                            </div>
                          </td>
                          <td class="px-4 py-3 text-center">
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-green-50 font-semibold text-green-700">{{ factor.counts['Totally Agree'] ?? 0 }}</span>
                          </td>
                          <td class="px-4 py-3 text-center">
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-blue-50 font-semibold text-blue-700">{{ factor.counts['Agree'] ?? 0 }}</span>
                          </td>
                          <td class="px-4 py-3 text-center">
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-orange-50 font-semibold text-orange-700">{{ factor.counts['Disagree'] ?? 0 }}</span>
                          </td>
                          <td class="px-4 py-3 text-center">
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-red-50 font-semibold text-red-700">{{ factor.counts['Totally Disagree'] ?? 0 }}</span>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  <div v-else class="rounded-lg border border-dashed border-gray-300 py-10 text-center text-sm text-gray-500">
                    {{ t('Try changing the filters to see risk factors') }}
                  </div>
                </div>
              </div>

              <section class="p-5">
                <div class="mb-4 flex items-start justify-between gap-3">
                  <h2 class="text-2xl font-bold text-gray-800">{{ t('Analysis by Department') }}</h2>
                  <span :class="statusClass(climaContent.sections.analysis_department.status)">{{ statusLabel(climaContent.sections.analysis_department.status) }}</span>
                </div>
                <RichTextEditor
                  v-if="effectiveCanManage"
                  v-model="sectionDrafts.analysis_department"
                />
                <RichTextEditor
                  v-else
                  :model-value="sectionDrafts.analysis_department || `<p>${t('No published content yet.')}</p>`"
                  :readonly="true"
                />
                <div v-if="effectiveCanManage" class="mt-3 flex gap-2">
                  <button @click="saveSection('analysis_department', 'draft')" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">{{ t('Save Draft') }}</button>
                  <button @click="saveSection('analysis_department', 'published')" class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-700">{{ t('Publish') }}</button>
                </div>
              </section>

              <section class="p-5">
                <div class="mb-4 flex items-start justify-between gap-3">
                  <h2 class="text-2xl font-bold text-gray-800">{{ t('Analysis by Position') }}</h2>
                  <span :class="statusClass(climaContent.sections.analysis_position.status)">{{ statusLabel(climaContent.sections.analysis_position.status) }}</span>
                </div>
                <p class="text-xs text-gray-500 mb-3">{{ t('Describe behavior by role type and where support actions are needed.') }}</p>
                <RichTextEditor
                  v-if="effectiveCanManage"
                  v-model="sectionDrafts.analysis_position"
                />
                <RichTextEditor
                  v-else
                  :model-value="sectionDrafts.analysis_position || `<p>${t('No published content yet.')}</p>`"
                  :readonly="true"
                />
                <div v-if="effectiveCanManage" class="mt-3 flex gap-2">
                  <button @click="saveSection('analysis_position', 'draft')" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">{{ t('Save Draft') }}</button>
                  <button @click="saveSection('analysis_position', 'published')" class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-700">{{ t('Publish') }}</button>
                </div>
              </section>
            </div>

            <div v-show="activeTab === 'recomendaciones'" class="animate-fade-in space-y-4">
              <!-- Structured factors table -->
              <section class="p-5">
                <div class="mb-4 flex items-start justify-between gap-3">
                  <div>
                    <h2 class="text-2xl font-bold text-gray-800">{{ t('Recommendations') }}</h2>
                    <p class="mt-0.5 text-sm text-gray-500">{{ t('Factor / Action / Department') }}</p>
                  </div>
                  <span :class="statusClass(climaContent.sections.recommendations_factors.status)">{{ statusLabel(climaContent.sections.recommendations_factors.status) }}</span>
                </div>
                <RecommendationFactorsBuilder
                  v-model="sectionDrafts.recommendations_factors"
                  :readonly="!effectiveCanManage"
                />
                <div v-if="effectiveCanManage" class="mt-4 flex gap-2">
                  <button @click="saveSection('recommendations_factors', 'draft')" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">{{ t('Save Draft') }}</button>
                  <button @click="saveSection('recommendations_factors', 'published')" class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-700">{{ t('Publish') }}</button>
                </div>
              </section>

              <!-- Free-text recommendations -->
              <section class="p-5">
                <div class="mb-4 flex items-start justify-between gap-3">
                  <h2 class="text-2xl font-bold text-gray-800">{{ t('Additional Notes') }}</h2>
                  <span :class="statusClass(climaContent.sections.recommendations.status)">{{ statusLabel(climaContent.sections.recommendations.status) }}</span>
                </div>
                <RichTextEditor
                  v-if="effectiveCanManage"
                  v-model="sectionDrafts.recommendations"
                />
                <RichTextEditor
                  v-else
                  :model-value="sectionDrafts.recommendations || `<p>${t('No published content yet.')}</p>`"
                  :readonly="true"
                />
                <div v-if="effectiveCanManage" class="mt-3 flex gap-2">
                  <button @click="saveSection('recommendations', 'draft')" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">{{ t('Save Draft') }}</button>
                  <button @click="saveSection('recommendations', 'published')" class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-700">{{ t('Publish') }}</button>
                </div>
              </section>
            </div>

            <div v-show="activeTab === 'foda'" class="animate-fade-in">
              <section class="p-5">
                <div class="mb-4 flex items-center justify-between gap-3">
                  <h3 class="text-lg font-semibold text-gray-900">{{ t('SWOT') }}</h3>
                  <span :class="statusClass(climaContent.sections.foda.status)">{{ statusLabel(climaContent.sections.foda.status) }}</span>
                </div>
                <FodaBuilder
                  v-model="sectionDrafts.foda"
                  :readonly="!effectiveCanManage"
                />
                <div v-if="effectiveCanManage" class="mt-4 flex gap-2">
                  <button @click="saveSection('foda', 'draft')" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">{{ t('Save Draft') }}</button>
                  <button @click="saveSection('foda', 'published')" class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-700">{{ t('Publish') }}</button>
                </div>
              </section>
            </div>

            <div v-show="activeTab === 'conclusions'" class="animate-fade-in">
              <ConclusionsBuilderTab
                v-model="conclusionsDraft"
                :organization-name="dashboardData.organization.name"
                :work-center-id="workCenter.id"
                :files="conclusionsContent.files"
                :section-status="conclusionsContent.section.status"
                :can-manage="effectiveCanManage"
              />
            </div>

            <div v-show="activeTab === 'report'" class="animate-fade-in">
              <ReportCardBuilder
                v-model="sectionDrafts.report_card_config"
                :readonly="!effectiveCanManage"
                :reports="climaContent.reports"
                :work-center-id="workCenter.id"
                :locale="locale"
                @save="saveSection('report_card_config', $event)"
                @toggle-publish="toggleReportPublish"
                @set-active="setActiveReport"
                @delete-report="deleteReport"
              />
            </div>

            <div v-show="activeTab === 'evidence'" class="animate-fade-in space-y-5">
              <!-- Upload zone (managers only) -->
              <div v-if="effectiveCanManage" class="rounded-xl border border-gray-200 bg-white p-5">
                <div class="mb-3 flex items-center justify-between">
                  <h3 class="text-sm font-semibold text-gray-700">{{ t('Upload photos') }}</h3>
                  <span v-if="evidenceUploadQueue.length" class="rounded-full bg-teal-100 px-2.5 py-0.5 text-xs font-semibold text-teal-700">
                    {{ t('Uploading') }} {{ evidenceUploadQueue.length }}…
                  </span>
                </div>
                <FileDropZone
                  multiple
                  type="image"
                  accept="image/png,image/jpeg,image/webp"
                  :language="locale"
                  :uploading="evidenceUploadQueue.length > 0"
                  @files-selected="uploadEvidenceFiles"
                />
              </div>

              <!-- Gallery -->
              <div v-if="climaContent.evidences.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="evidence in climaContent.evidences" :key="evidence.id" class="group relative overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                  <div class="relative">
                    <img :src="evidence.preview_url" :alt="evidence.title || evidence.original_filename" class="h-48 w-full object-cover" />
                    <div v-if="!evidence.is_published" class="absolute left-2 top-2 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700">{{ t('Draft') }}</div>
                  </div>
                  <div class="p-3">
                    <p class="truncate text-sm font-medium text-gray-800">{{ evidence.title || evidence.original_filename }}</p>
                    <p class="mt-0.5 text-xs text-gray-400">{{ evidence.file_size_human }}</p>
                    <div class="mt-3 flex flex-wrap gap-1.5">
                      <a :href="route('work-centers.clima.evidences.download', [workCenter.id, evidence.id])" class="rounded-lg border border-gray-300 px-2.5 py-1 text-xs font-semibold text-gray-700 hover:bg-gray-100">↓</a>
                      <button v-if="effectiveCanManage" @click="toggleEvidencePublish(evidence.id)" class="rounded-lg border border-gray-300 px-2.5 py-1 text-xs font-semibold text-gray-700 hover:bg-gray-100">{{ evidence.is_published ? t('Unpublish') : t('Publish') }}</button>
                      <button v-if="effectiveCanManage" @click="deleteEvidence(evidence.id)" class="rounded-lg border border-red-200 px-2.5 py-1 text-xs font-semibold text-red-600 hover:bg-red-50">{{ t('Delete') }}</button>
                    </div>
                  </div>
                </div>
              </div>
              <div v-else class="rounded-xl border border-dashed border-gray-300 bg-gray-50 py-14 text-center text-sm text-gray-400 italic">
                {{ t('No photos uploaded yet.') }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Dashboard>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import Dashboard from '../../Layouts/Dashboard.vue';
import DemographicDataTab from '@/Components/Organization/DemographicDataTab.vue';
import ClimaLaboralResultsTab from '@/Components/Organization/ClimaLaboralResultsTab.vue';
import LanguageSwitcher from '@/Components/LanguageSwitcher.vue';
import RichTextEditor from '@/Components/RichTextEditor.vue';
import RecommendationFactorsBuilder from '@/Components/WorkCenter/RecommendationFactorsBuilder.vue';
import ReportCardBuilder from '@/Components/WorkCenter/ReportCardBuilder.vue';
import FileDropZone from '@/Components/WorkCenter/FileDropZone.vue';
import FodaBuilder from '@/Components/WorkCenter/FodaBuilder.vue';
import ConclusionsBuilderTab from '@/Components/Organization/ConclusionsBuilderTab.vue';
import { useTranslations } from '@/composables/useTranslations';

const { t, locale } = useTranslations();

interface Tab {
  key: string;
  labelKey: string;
}

interface DemographicDetails {
  genders: string[];
  contract_types: string[];
  positions: string[];
  areas: string[];
  shifts: string[];
  total_evaluations: number;
}

interface DashboardData {
  organization: {
    id: string;
    name: string;
    logo: string | null;
  };
  demographic_details: DemographicDetails;
}

interface WorkCenter {
  id: string;
  name: string;
  code: string;
}

interface DimensionScore {
  name: string;
  score: number;
  interpretation: string;
}

interface Evaluation {
  id: string;
  folio: string;
  personal_folio: string;
  total_score: number;
  interpretation: string;
  demographic_data?: Record<string, unknown>;
  demographicData?: {
    gender?: string;
    contract_type?: string;
    position?: string;
    department?: string;
    work_schedule?: string;
  };
  dimensions?: DimensionScore[];
}

interface ClimaSectionState {
  content: string | null;
  status: 'draft' | 'published';
}

interface ClimaReportItem {
  id: number;
  title: string;
  language: string;
  original_filename: string;
  file_size_human: string;
  is_published: boolean;
  is_active: boolean;
  created_at: string | null;
}

interface ClimaEvidenceItem {
  id: number;
  title: string | null;
  description: string | null;
  preview_url: string;
  original_filename: string;
  file_size_human: string;
  is_published: boolean;
}

interface ConclusionsFileItem {
  id: number;
  slot: number;
  title: string;
  color: string;
  original_filename: string;
  file_size_human: string;
  is_published: boolean;
}

interface Props {
  dashboardData: DashboardData;
  workCenter: WorkCenter;
  evaluations?: Evaluation[];
  canManageClima: boolean;
  climaContent: {
    sections: {
      analysis_department: ClimaSectionState;
      analysis_position: ClimaSectionState;
      recommendations: ClimaSectionState;
      recommendations_factors: ClimaSectionState;
      report_card_config: ClimaSectionState;
      foda: ClimaSectionState;
      conclusions: ClimaSectionState;
    };
    reports: ClimaReportItem[];
    evidences: ClimaEvidenceItem[];
  };
  conclusionsContent: {
    section: { content: string | null; status: 'draft' | 'published' };
    files: Record<string, ConclusionsFileItem>;
  };
}

const props = withDefaults(defineProps<Props>(), {
  evaluations: () => [],
});

const tabs: Tab[] = [
  { key: 'demographic', labelKey: 'Demographic Data' },
  { key: 'results', labelKey: 'Results' },
  { key: 'analysis', labelKey: 'Analysis' },
  { key: 'recomendaciones', labelKey: 'Recommendations' },
  { key: 'report', labelKey: 'Report' },
  { key: 'evidence', labelKey: 'Evidence' },
  { key: 'foda', labelKey: 'SWOT' },
  { key: 'conclusions', labelKey: 'Conclusions' },
];

const translatedTabs = computed(() =>
  tabs.map(tab => ({
    key: tab.key,
    label: t(tab.labelKey),
  }))
);

const activeTab = ref<string>('demographic');
const evaluations = ref<Evaluation[]>(props.evaluations || []);
const conclusionsDraft = ref<string>(props.conclusionsContent.section.content ?? '{}');

const isPreviewMode = ref<boolean>(false);
const effectiveCanManage = computed(() => props.canManageClima && !isPreviewMode.value);

const analysisFilters = reactive({
  gender: '',
  contract_type: '',
  position: '',
  area: '',
  shift: '',
});

const filteredAnalysisEvaluations = computed(() =>
  evaluations.value.filter(evaluation => {
    const demo = evaluation.demographicData ?? {};
    if (analysisFilters.gender && demo.gender !== analysisFilters.gender) { return false; }
    if (analysisFilters.contract_type && demo.contract_type !== analysisFilters.contract_type) { return false; }
    if (analysisFilters.position && demo.position !== analysisFilters.position) { return false; }
    if (analysisFilters.area && demo.department !== analysisFilters.area) { return false; }
    if (analysisFilters.shift && demo.work_schedule !== analysisFilters.shift) { return false; }
    return true;
  }));

const topRiskFactors = computed(() => {
  const interpretationMap: Record<string, string> = {
    'Totalmente de Acuerdo': 'Totally Agree',
    'De Acuerdo': 'Agree',
    'Desacuerdo': 'Disagree',
    'Totalmente Desacuerdo': 'Totally Disagree',
  };
  const factorMap: Record<string, Record<string, number>> = {};

  filteredAnalysisEvaluations.value.forEach(evaluation => {
    if (!evaluation.dimensions || !Array.isArray(evaluation.dimensions)) { return; }
    evaluation.dimensions.forEach(dimension => {
      if (!factorMap[dimension.name]) {
        factorMap[dimension.name] = { 'Totally Agree': 0, 'Agree': 0, 'Disagree': 0, 'Totally Disagree': 0 };
      }
      const key = interpretationMap[dimension.interpretation] ?? dimension.interpretation ?? '';
      if (factorMap[dimension.name][key] !== undefined) {
        factorMap[dimension.name][key]++;
      }
    });
  });

  return Object.entries(factorMap)
    .map(([name, counts]) => ({
      name,
      counts,
      disagreementSum: (counts['Disagree'] ?? 0) + (counts['Totally Disagree'] ?? 0),
    }))
    .sort((a, b) => b.disagreementSum - a.disagreementSum);
});

const riskFactorBadgeClass = (index: number): string => {
  if (index === 0) { return 'bg-red-600 text-white'; }
  if (index === 1) { return 'bg-orange-500 text-white'; }
  if (index === 2) { return 'bg-yellow-500 text-white'; }
  return 'bg-slate-400 text-white';
};

const resetAnalysisFilters = (): void => {
  analysisFilters.gender = '';
  analysisFilters.contract_type = '';
  analysisFilters.position = '';
  analysisFilters.area = '';
  analysisFilters.shift = '';
};

const route = (...args: unknown[]): string => (window as unknown as Window & { route: (...params: unknown[]) => string }).route(...args);

const sectionDrafts = reactive({
  analysis_department: props.climaContent.sections.analysis_department.content ?? '',
  analysis_position: props.climaContent.sections.analysis_position.content ?? '',
  recommendations: props.climaContent.sections.recommendations.content ?? '',
  recommendations_factors: props.climaContent.sections.recommendations_factors.content ?? '[]',
  report_card_config: props.climaContent.sections.report_card_config.content ?? '{}',
  foda: props.climaContent.sections.foda.content ?? '[]',
  conclusions: props.climaContent.sections.conclusions.content ?? '',
});

const sectionForm = useForm({
  section_key: '',
  content: '',
  status: 'draft',
});

const reportForm = useForm({
  title: '',
  report_file: null as File | null,
  is_published: true,
  is_active: true,
});

const evidenceForm = useForm({
  title: '',
  description: '',
  evidence_file: null as File | null,
  is_published: true,
});

const evidenceUploadQueue = ref<number[]>([]);

const uploadEvidenceFiles = (files: File[]): void => {
  files.forEach((file, i) => {
    const id = Date.now() + i;
    evidenceUploadQueue.value.push(id);

    const form = useForm({
      title: null as string | null,
      description: null as string | null,
      evidence_file: file as File | null,
      is_published: true,
    });

    form.post(route('work-centers.clima.evidences.store', props.workCenter.id), {
      forceFormData: true,
      preserveScroll: true,
      onFinish: () => {
        evidenceUploadQueue.value = evidenceUploadQueue.value.filter(q => q !== id);
      },
    });
  });
};

const activePublishedReport = computed(() => {
  return props.climaContent.reports.find(report => report.is_active && report.is_published)
    ?? props.climaContent.reports.find(report => report.is_published)
    ?? null;
});

const statusLabel = (status: 'draft' | 'published'): string => {
  return status === 'published' ? t('Published') : t('Draft');
};

const statusClass = (status: 'draft' | 'published'): string => {
  return status === 'published'
    ? 'rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700'
    : 'rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700';
};

const saveSection = (sectionKey: string, status: 'draft' | 'published'): void => {
  sectionForm.section_key = sectionKey;
  sectionForm.content = sectionDrafts[sectionKey as keyof typeof sectionDrafts];
  sectionForm.status = status;

  sectionForm.post(route('work-centers.clima.sections.upsert', props.workCenter.id), {
    preserveScroll: true,
  });
};

const onReportFileChange = (event: Event): void => {
  const target = event.target as HTMLInputElement;
  reportForm.report_file = target.files?.[0] ?? null;
};

const submitReport = (): void => {
  reportForm.post(route('work-centers.clima.reports.store', props.workCenter.id), {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      reportForm.reset('title', 'report_file');
      reportForm.is_published = true;
      reportForm.is_active = true;
    },
  });
};

const toggleReportPublish = (reportId: number): void => {
  router.patch(route('work-centers.clima.reports.toggle-publish', [props.workCenter.id, reportId]), {}, { preserveScroll: true });
};

const setActiveReport = (reportId: number): void => {
  router.patch(route('work-centers.clima.reports.set-active', [props.workCenter.id, reportId]), {}, { preserveScroll: true });
};

const deleteReport = (reportId: number): void => {
  router.delete(route('work-centers.clima.reports.destroy', [props.workCenter.id, reportId]), { preserveScroll: true });
};

const onEvidenceFileChange = (_event: Event): void => { /* replaced by uploadEvidenceFiles */ };

const submitEvidence = (): void => { /* replaced by uploadEvidenceFiles */ };

const toggleEvidencePublish = (evidenceId: number): void => {
  router.patch(route('work-centers.clima.evidences.toggle-publish', [props.workCenter.id, evidenceId]), {}, { preserveScroll: true });
};

const deleteEvidence = (evidenceId: number): void => {
  router.delete(route('work-centers.clima.evidences.destroy', [props.workCenter.id, evidenceId]), { preserveScroll: true });
};
</script>

<style scoped>
@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

.animate-fade-in {
  animation: fadeIn 0.3s ease-in;
}
</style>
