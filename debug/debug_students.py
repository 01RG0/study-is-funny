#!/usr/bin/env python3
"""
Debug script to inspect student data in MongoDB
Usage: python debug_students.py
"""

from pymongo import MongoClient
from pprint import pprint

def main():
    try:
        # Connect to MongoDB (adjust connection string if needed)
        client = MongoClient('mongodb://localhost:27017/')
        db = client['study_is_funny']  # Adjust database name if needed
        
        print("=== Checking all_students_view collection ===")
        # Find all students for parent phone 01202118649
        parent_phone = "01202118649"
        students = list(db.all_students_view.find({
            'parentPhone': {'$in': [
                parent_phone,
                f"+2{parent_phone[1:]}",
                parent_phone[1:],
                f"20{parent_phone[1:]}"
            ]}
        }))
        
        print(f"\nFound {len(students)} records for parent {parent_phone}:")
        for i, student in enumerate(students, 1):
            print(f"\n--- Record {i} ---")
            print(f"Name: {student.get('studentName', student.get('name', 'N/A'))}")
            print(f"Phone: {student.get('phone', 'N/A')}")
            print(f"Grade: {student.get('grade', 'N/A')}")
            print(f"Subject: {student.get('subject', 'N/A')}")
            
            # Show session fields
            session_fields = {k: v for k, v in student.items() if k.startswith('session_')}
            if session_fields:
                print(f"Session fields ({len(session_fields)}):")
                for k, v in list(session_fields.items())[:5]:  # Show first 5
                    print(f"  {k}: {v}")
                if len(session_fields) > 5:
                    print(f"  ... and {len(session_fields) - 5} more")
        
        print("\n=== Checking users collection ===")
        users = list(db.users.find({
            'parentPhone': {'$in': [
                parent_phone,
                f"+2{parent_phone[1:]}",
                parent_phone[1:],
                f"20{parent_phone[1:]}"
            ]}
        }))
        
        print(f"\nFound {len(users)} records in users collection:")
        for i, user in enumerate(users, 1):
            print(f"\n--- User {i} ---")
            print(f"Name: {user.get('name', 'N/A')}")
            print(f"Phone: {user.get('phone', 'N/A')}")
            print(f"Grade: {user.get('grade', 'N/A')}")
            print(f"Subjects: {user.get('subjects', 'N/A')}")
            print(f"Subject: {user.get('subject', 'N/A')}")
            
            # Show session fields
            session_fields = {k: v for k, v in user.items() if k.startswith('session_')}
            if session_fields:
                print(f"Session fields ({len(session_fields)}):")
                for k, v in list(session_fields.items())[:5]:
                    print(f"  {k}: {v}")
                if len(session_fields) > 5:
                    print(f"  ... and {len(session_fields) - 5} more")
        
        print("\n=== Checking for duplicate session data ===")
        # Check if different subjects have identical session data
        subject_sessions = {}
        for student in students:
            subject = student.get('subject', 'unknown')
            session_fields = {k: v for k, v in student.items() if k.startswith('session_')}
            if session_fields:
                if subject not in subject_sessions:
                    subject_sessions[subject] = []
                subject_sessions[subject].append(session_fields)
        
        for subject, sessions_list in subject_sessions.items():
            print(f"\nSubject: {subject}")
            for i, sessions in enumerate(sessions_list):
                print(f"  Record {i+1}: {len(sessions)} session fields")
            
            # Check if all sessions are identical
            if len(sessions_list) > 1:
                first = sessions_list[0]
                all_same = all(s == first for s in sessions_list[1:])
                print(f"  All records have identical session data: {all_same}")
        
        client.close()
        
    except Exception as e:
        print(f"Error: {e}")
        print("\nTroubleshooting:")
        print("1. Make sure MongoDB is running")
        print("2. Check database name: 'study_is_funny' (update if different)")
        print("3. Check connection string: 'mongodb://localhost:27017/'")

if __name__ == "__main__":
    main()
