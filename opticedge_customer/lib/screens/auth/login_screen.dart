import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../config/constants.dart';
import '../../core/providers/auth_provider.dart';

class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key});

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _phoneCtrl = TextEditingController();
  final _pinCtrl = TextEditingController();
  bool _obscurePin = true;

  @override
  void dispose() {
    _phoneCtrl.dispose();
    _pinCtrl.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    FocusScope.of(context).unfocus();
    await ref.read(authProvider.notifier).login(
          _phoneCtrl.text.trim(),
          _pinCtrl.text.trim(),
        );
  }

  @override
  Widget build(BuildContext context) {
    final auth = ref.watch(authProvider);

    return Scaffold(
      backgroundColor: Colors.white,
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.symmetric(horizontal: 24),
          child: Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                const SizedBox(height: 60),
                // Logo
                Container(
                  width: 72,
                  height: 72,
                  decoration: BoxDecoration(
                    color: AppConstants.primary.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(18),
                  ),
                  child: const Icon(Icons.phone_android, size: 36, color: AppConstants.primary),
                ),
                const SizedBox(height: 24),
                Text(
                  'Karibu!',
                  style: Theme.of(context).textTheme.headlineMedium?.copyWith(
                        fontWeight: FontWeight.bold,
                        color: AppConstants.primary,
                      ),
                ),
                const SizedBox(height: 4),
                Text(
                  'Ingia kwa namba yako ya simu na PIN',
                  style: Theme.of(context).textTheme.bodyLarge?.copyWith(color: Colors.grey[600]),
                ),
                const SizedBox(height: 40),

                // Phone
                TextFormField(
                  controller: _phoneCtrl,
                  keyboardType: TextInputType.phone,
                  inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                  decoration: const InputDecoration(
                    labelText: 'Namba ya Simu',
                    hintText: '07XXXXXXXX',
                    prefixIcon: Icon(Icons.phone_rounded),
                  ),
                  validator: (v) {
                    if (v == null || v.trim().length < 9) return 'Weka namba sahihi ya simu';
                    return null;
                  },
                ),
                const SizedBox(height: 16),

                // PIN
                TextFormField(
                  controller: _pinCtrl,
                  keyboardType: TextInputType.number,
                  obscureText: _obscurePin,
                  maxLength: 6,
                  inputFormatters: [FilteringTextInputFormatter.digitsOnly],
                  decoration: InputDecoration(
                    labelText: 'PIN',
                    hintText: '****',
                    prefixIcon: const Icon(Icons.lock_rounded),
                    counterText: '',
                    suffixIcon: IconButton(
                      icon: Icon(_obscurePin ? Icons.visibility_off : Icons.visibility),
                      onPressed: () => setState(() => _obscurePin = !_obscurePin),
                    ),
                  ),
                  validator: (v) {
                    if (v == null || v.length < 4) return 'PIN lazima iwe tarakimu 4-6';
                    return null;
                  },
                ),
                const SizedBox(height: 8),

                // Error
                if (auth.error != null)
                  Padding(
                    padding: const EdgeInsets.only(bottom: 12),
                    child: Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: AppConstants.danger.withValues(alpha: 0.1),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.error_outline, color: AppConstants.danger, size: 20),
                          const SizedBox(width: 8),
                          Expanded(
                            child: Text(
                              auth.error!,
                              style: const TextStyle(color: AppConstants.danger, fontSize: 13),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),

                const SizedBox(height: 8),

                // Submit
                ElevatedButton(
                  onPressed: auth.isLoading ? null : _submit,
                  child: auth.isLoading
                      ? const SizedBox(
                          height: 20,
                          width: 20,
                          child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                        )
                      : const Text('Ingia'),
                ),

                const SizedBox(height: 32),
                Center(
                  child: Text(
                    'Opticedge Customer v1.0',
                    style: TextStyle(color: Colors.grey[400], fontSize: 12),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
