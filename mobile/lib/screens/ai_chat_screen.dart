import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:dio/dio.dart';
import '../providers/providers.dart';

class AiChatScreen extends ConsumerStatefulWidget {
  const AiChatScreen({super.key});

  @override
  ConsumerState<AiChatScreen> createState() => _AiChatScreenState();
}

class _AiChatScreenState extends ConsumerState<AiChatScreen> {
  final TextEditingController _messageController = TextEditingController();
  final ScrollController _scrollController = ScrollController();
  
  final List<Map<String, String>> _messages = [
    {
      'role': 'assistant',
      'content': 'Assalomu alaykum! Men sizning sun\'iy intellekt yordamida ishlaydigan agronom yordamchingizman. Tuproq unumdorligi, ekinlarni o\'g\'itlash, sug\'orish yoki kasalliklarga qarshi kurash bo\'yicha qanday savolingiz bor?',
    }
  ];

  bool _isTyping = false;

  final List<String> _quickSuggestions = [
    'Tuproq unumdorligini qanday oshirish mumkin?',
    'G\'o\'za (paxta) uchun qanday o\'g\'itlar tavsiya etiladi?',
    'Namlik darajasi 40% bo\'lsa, qachon sug\'orish kerak?',
    'Kasalliklarga qarshi maslahat bering',
  ];

  @override
  void dispose() {
    _messageController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  void _scrollToBottom() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_scrollController.hasClients) {
        _scrollController.animateTo(
          _scrollController.position.maxScrollExtent,
          duration: const Duration(milliseconds: 300),
          curve: Curves.easeOut,
        );
      }
    });
  }

  Future<void> _sendMessage(String text) async {
    if (text.trim().isEmpty) return;

    setState(() {
      _messages.add({
        'role': 'user',
        'content': text.trim(),
      });
      _isTyping = true;
    });
    _messageController.clear();
    _scrollToBottom();

    // Backend history payload
    // Exclude the initial greeting as it's a client-side message
    final historyPayload = _messages
        .skip(1)
        .take(_messages.length - 2) // exclude the latest user message which goes to 'message' parameter
        .map((m) => {
              'role': m['role']!,
              'content': m['content']!,
            })
        .toList();

    final api = ref.read(apiServiceProvider);

    try {
      final res = await api.askAiAgronomist(
        message: text.trim(),
        history: historyPayload.isEmpty ? null : historyPayload,
      );

      if (res.data['status'] == 'success' && mounted) {
        setState(() {
          _messages.add({
            'role': 'assistant',
            'content': res.data['reply'] ?? 'Kechirasiz, javob olishda xatolik yuz berdi.',
          });
          _isTyping = false;
        });
        _scrollToBottom();
      }
    } catch (e) {
      if (mounted) {
        String errorMsg = 'Ulanishda xatolik yuz berdi. Iltimos, qayta urinib ko\'ring.';
        if (e is DioException && e.response?.data != null) {
          final data = e.response!.data;
          if (data is Map && data.containsKey('message')) {
            errorMsg = data['message'];
          }
        }
        setState(() {
          _messages.add({
            'role': 'assistant',
            'content': errorMsg,
          });
          _isTyping = false;
        });
        _scrollToBottom();
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC), // Slate/Grey warm light tone
      appBar: AppBar(
        backgroundColor: const Color(0xFF1A3C2A), // Forest green premium header
        foregroundColor: Colors.white,
        elevation: 2,
        title: Row(
          children: [
            const CircleAvatar(
              backgroundColor: Color(0xFFFFC107), // Amber robot theme background
              radius: 18,
              child: Icon(Icons.smart_toy_rounded, color: Colors.white, size: 20),
            ),
            const SizedBox(width: 10),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'AI Agronom Maslahatchi',
                  style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold),
                ),
                Row(
                  children: [
                    Container(
                      width: 8,
                      height: 8,
                      decoration: const BoxDecoration(
                        color: Color(0xFF10B981),
                        shape: BoxShape.circle,
                      ),
                    ),
                    const SizedBox(width: 4),
                    const Text(
                      'Online',
                      style: TextStyle(fontSize: 10, color: Colors.white70),
                    ),
                  ],
                )
              ],
            )
          ],
        ),
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios_new_rounded),
          onPressed: () => Navigator.pop(context),
        ),
      ),
      body: SafeArea(
        child: Column(
          children: [
            // Chat Message List
            Expanded(
              child: ListView.builder(
                controller: _scrollController,
                padding: const EdgeInsets.all(16),
                itemCount: _messages.length,
                itemBuilder: (context, index) {
                  final msg = _messages[index];
                  final isUser = msg['role'] == 'user';
                  return _buildMessageBubble(msg['content'] ?? '', isUser);
                },
              ),
            ),

            // Shimmer/Typing Indicator
            if (_isTyping) _buildTypingIndicator(),

            // Quick Suggestions List
            if (_messages.length == 1 && !_isTyping) _buildSuggestionsRow(),

            // Input Panel
            _buildInputPanel(),
          ],
        ),
      ),
    );
  }

  Widget _buildMessageBubble(String content, bool isUser) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12.0),
      child: Row(
        mainAxisAlignment: isUser ? MainAxisAlignment.end : MainAxisAlignment.start,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (!isUser) ...[
            const CircleAvatar(
              backgroundColor: Color(0xFFFFC107),
              radius: 14,
              child: Icon(Icons.smart_toy_rounded, color: Colors.white, size: 14),
            ),
            const SizedBox(width: 8),
          ],
          Flexible(
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
              decoration: BoxDecoration(
                color: isUser 
                    ? const Color(0xFF1A3C2A) // Premium Forest Green for user bubbles
                    : Colors.white,            // Clean white for AI responses
                borderRadius: BorderRadius.only(
                  topLeft: const Radius.circular(16),
                  topRight: const Radius.circular(16),
                  bottomLeft: Radius.circular(isUser ? 16 : 4),
                  bottomRight: Radius.circular(isUser ? 4 : 16),
                ),
                border: isUser 
                    ? null 
                    : Border.all(color: const Color(0xFFE2E8F0), width: 1), // subtle slate outline
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withOpacity(0.02),
                    blurRadius: 4,
                    offset: const Offset(0, 2),
                  ),
                ],
              ),
              child: Text(
                content,
                style: TextStyle(
                  color: isUser ? Colors.white : const Color(0xFF1E293B),
                  fontSize: 13.5,
                  height: 1.45,
                ),
              ),
            ),
          ),
          if (isUser) ...[
            const SizedBox(width: 8),
            CircleAvatar(
              backgroundColor: const Color(0xFF1A3C2A).withOpacity(0.1),
              radius: 14,
              child: const Icon(Icons.person_rounded, color: Color(0xFF1A3C2A), size: 14),
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildTypingIndicator() {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 8),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.start,
        children: [
          const CircleAvatar(
            backgroundColor: Color(0xFFFFC107),
            radius: 14,
            child: Icon(Icons.smart_toy_rounded, color: Colors.white, size: 14),
          ),
          const SizedBox(width: 8),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: const BorderRadius.only(
                topLeft: Radius.circular(16),
                topRight: Radius.circular(16),
                bottomLeft: Radius.circular(4),
                bottomRight: Radius.circular(16),
              ),
              border: Border.all(color: const Color(0xFFE2E8F0), width: 1),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(
                  'AI agronom o\'ylamoqda',
                  style: TextStyle(color: Colors.grey[600], fontSize: 12),
                ),
                const SizedBox(width: 6),
                const SizedBox(
                  width: 10,
                  height: 10,
                  child: CircularProgressIndicator(
                    strokeWidth: 2,
                    color: Color(0xFFFFC107),
                  ),
                )
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSuggestionsRow() {
    return Container(
      height: 48,
      margin: const EdgeInsets.only(bottom: 8),
      child: ListView.builder(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 16),
        itemCount: _quickSuggestions.length,
        itemBuilder: (context, index) {
          final text = _quickSuggestions[index];
          return Padding(
            padding: const EdgeInsets.only(right: 8.0),
            child: ActionChip(
              onPressed: () => _sendMessage(text),
              backgroundColor: Colors.white,
              surfaceTintColor: Colors.white,
              shadowColor: Colors.black.withOpacity(0.1),
              elevation: 1,
              side: const BorderSide(color: Color(0xFFE2E8F0), width: 1),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
              label: Text(
                text,
                style: const TextStyle(
                  color: Color(0xFF1E293B),
                  fontSize: 12,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _buildInputPanel() {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
      decoration: BoxDecoration(
        color: Colors.white,
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.04),
            blurRadius: 10,
            offset: const Offset(0, -2),
          ),
        ],
        border: const Border(top: BorderSide(color: Color(0xFFE2E8F0), width: 1)),
      ),
      child: Row(
        children: [
          Expanded(
            child: Container(
              decoration: BoxDecoration(
                color: const Color(0xFFF1F5F9), // light grey/slate input background
                borderRadius: BorderRadius.circular(24),
              ),
              child: TextFormField(
                controller: _messageController,
                textCapitalization: TextCapitalization.sentences,
                style: const TextStyle(fontSize: 14, color: Color(0xFF1E293B)),
                maxLines: null,
                decoration: const InputDecoration(
                  hintText: 'Savolingizni bu yerga yozing...',
                  hintStyle: TextStyle(color: Colors.grey, fontSize: 13),
                  contentPadding: EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                  border: InputBorder.none,
                ),
              ),
            ),
          ),
          const SizedBox(width: 8),
          GestureDetector(
            onTap: () => _sendMessage(_messageController.text),
            child: CircleAvatar(
              backgroundColor: const Color(0xFF1A3C2A), // Forest Green send button
              radius: 20,
              child: const Icon(Icons.send_rounded, color: Colors.white, size: 18),
            ),
          ),
        ],
      ),
    );
  }
}
