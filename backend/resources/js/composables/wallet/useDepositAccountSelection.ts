import { ref } from 'vue'

/**
 * Cuenta de la plataforma a la que el jugador dice haber pagado (D1). La
 * elige OperationStepDepositReceipt y la envía useWallet al depositar; vive
 * en un ref de módulo para no tener que pasarla por WalletOperationModal y la
 * página de billetera.
 */
const selectedDepositAccountId = ref<string | null>(null)

export function useDepositAccountSelection() {
  return { selectedDepositAccountId }
}
