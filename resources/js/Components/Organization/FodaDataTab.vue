<template>
    <div>
        <!-- Header -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-2">{{ t('SWOT') }}</h2>
        </div>
    </div>

    <div class="space-y-6">
        <table class="w-full table-fixed border-collapse">
            <thead>
                <tr>
                    <th class="border-b border-gray-200 p-4 pt-0 pb-3 text-left font-medium text-black">
                        {{ t('Factor') }}
                    </th>
                    <th class="border-b border-gray-200 p-4 pt-0 pb-3 text-left font-medium text-black">
                        {{ t('Strength') }}
                    </th>
                    <th class="border-b border-gray-200 p-4 pt-0 pb-3 text-left font-medium text-black">
                        {{ t('Weakness') }}
                    </th>
                    <th class="border-b border-gray-200 p-4 pt-0 pb-3 text-left font-medium text-black">
                        {{ t('Opportunity') }}
                    </th>
                    <th class="border-b border-gray-200 p-4 pt-0 pb-3 text-left font-medium text-black">
                        {{ t('Threat') }}
                    </th>
                </tr>
            </thead>

            <tbody class="bg-white">
                <tr v-for="foda in FODAData" :key="foda.factor" class="text-justify">
                    <td class="border-b border-gray-200 p-4 text-gray-800  px-6 py-4">
                        {{ foda.factor }}
                    </td>
                    <td class="border-b border-gray-100 p-4 text-gray-800  px-6 py-4">
                        {{ foda.fortaleza }}
                    </td>
                    <td class="border-b border-gray-100 p-4 text-gray-800  px-6 py-4">
                        {{ foda.debilidad }}
                    </td>
                    <td class="border-b border-gray-100 p-4 text-gray-800  px-6 py-4">
                        {{ foda.oportunidad }}
                    </td>
                    <td class="border-b border-gray-100 p-4 text-gray-800  px-6 py-4">
                        {{ foda.amenaza }}
                    </td>
                </tr>

            </tbody>
        </table>
    </div>
</template>


<script setup lang="ts">
import { useTranslations } from '@/Composables/useTranslations';

const { t } = useTranslations();

export interface FODAItem {
  factor: string;
  fortaleza: string;
  debilidad: string;
  oportunidad: string;
  amenaza: string;
}

const FODAData: FODAItem[] = [
  {
    factor: t('Safe Work Environment'),
    fortaleza: t('Safe Work Environment Strength'),
    debilidad: t('Safe Work Environment Weakness'),
    oportunidad: t('Safe Work Environment Opportunity'),
    amenaza: t('Safe Work Environment Threat')
  },
  {
    factor: t('Job Security'),
    fortaleza: t('Job Security Strength'),
    debilidad: t('Job Security Weakness'),
    oportunidad: t('Job Security Opportunity'),
    amenaza: t('Job Security Threat')
  },
  {
    factor: t('Fair Compensation'),
    fortaleza: t('Fair Compensation Strength'),
    debilidad: t('Fair Compensation Weakness'),
    oportunidad: t('Fair Compensation Opportunity'),
    amenaza: t('Fair Compensation Threat')
  },
  {
    factor: t('Open Communication'),
    fortaleza: t('Open Communication Strength'),
    debilidad: t('Open Communication Weakness'),
    oportunidad: t('Open Communication Opportunity'),
    amenaza: t('Open Communication Threat')
  },
  {
    factor: t('Employee Participation'),
    fortaleza: t('Employee Participation Strength'),
    debilidad: t('Employee Participation Weakness'),
    oportunidad: t('Employee Participation Opportunity'),
    amenaza: t('Employee Participation Threat')
  },
  {
    factor: t('Recognition and Reward'),
    fortaleza: t('Recognition and Reward Strength'),
    debilidad: t('Recognition and Reward Weakness'),
    oportunidad: t('Recognition and Reward Opportunity'),
    amenaza: t('Recognition and Reward Threat')
  },
  {
    factor: t('Training and Development'),
    fortaleza: t('Training and Development Strength'),
    debilidad: t('Training and Development Weakness'),
    oportunidad: t('Training and Development Opportunity'),
    amenaza: t('Training and Development Threat')
  },
  {
    factor: t('Work-Life Balance'),
    fortaleza: t('Work-Life Balance Strength'),
    debilidad: t('Work-Life Balance Weakness'),
    oportunidad: t('Work-Life Balance Opportunity'),
    amenaza: t('Work-Life Balance Threat')
  },
  {
    factor: t('Professional Advancement'),
    fortaleza: t('Professional Advancement Strength'),
    debilidad: t('Professional Advancement Weakness'),
    oportunidad: t('Professional Advancement Opportunity'),
    amenaza: t('Professional Advancement Threat')
  },
  {
    factor: t('Employee Support'),
    fortaleza: t('Employee Support Strength'),
    debilidad: t('Employee Support Weakness'),
    oportunidad: t('Employee Support Opportunity'),
    amenaza: t('Employee Support Threat')
  }
];
</script>