import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/auth_provider.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  LoginScreenState createState() => LoginScreenState();
}

class LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _userCtrl = TextEditingController();
  final _passCtrl = TextEditingController();
  bool _obscurePass = true;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) async {
      if (!mounted) return;
      final auth = context.read<AuthProvider>();
      await auth.loadToken();
      if (!mounted) return;
      if (auth.isLoggedIn) {
        Navigator.pushReplacementNamed(context, '/wo-list');
      }
    });
  }

  @override
  void dispose() {
    _userCtrl.dispose();
    _passCtrl.dispose();
    super.dispose();
  }

  Future<void> _login() async {
    if (!_formKey.currentState!.validate()) return;
    final auth = context.read<AuthProvider>();
    final messenger = ScaffoldMessenger.of(context);
    final navigator = Navigator.of(context);
    final ok = await auth.login(_userCtrl.text, _passCtrl.text);
    if (!mounted) return;
    if (ok) {
      navigator.pushReplacementNamed('/wo-list');
    } else {
      messenger.showSnackBar(
        SnackBar(
          content: Text(auth.error ?? 'Login gagal. Cek email/password.'),
          backgroundColor: Colors.red.shade700,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    return Scaffold(
      body: Center(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(32),
          child: Form(
            key: _formKey,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Text('Material Tracker',
                    style:
                        TextStyle(fontSize: 28, fontWeight: FontWeight.bold)),
                const SizedBox(height: 32),
                TextFormField(
                  controller: _userCtrl,
                  decoration: const InputDecoration(
                    labelText: 'Username / Email',
                    helperText: 'Username atau email akun',
                  ),
                  keyboardType: TextInputType.text,
                  autofillHints: const [
                    AutofillHints.username,
                    AutofillHints.email
                  ],
                  validator: (v) => v != null && v.trim().isNotEmpty
                      ? null
                      : 'Username wajib diisi',
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _passCtrl,
                  obscureText: _obscurePass,
                  autofillHints: const [AutofillHints.password],
                  decoration: InputDecoration(
                    labelText: 'Password',
                    helperText: 'Min 4 karakter',
                    suffixIcon: IconButton(
                      tooltip: _obscurePass
                          ? 'Tampilkan password'
                          : 'Sembunyikan password',
                      icon: Icon(_obscurePass
                          ? Icons.visibility
                          : Icons.visibility_off),
                      onPressed: () =>
                          setState(() => _obscurePass = !_obscurePass),
                    ),
                  ),
                  validator: (v) =>
                      v != null && v.length > 3 ? null : 'Min 4 chars',
                ),
                const SizedBox(height: 24),
                if (auth.isLoading)
                  const CircularProgressIndicator()
                else
                  FilledButton(
                    onPressed: _login,
                    style: FilledButton.styleFrom(
                        minimumSize: const Size(double.infinity, 48)),
                    child: const Text('Login'),
                  ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
